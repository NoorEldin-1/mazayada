<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\Auction;
use App\Models\Delivery;
use App\Models\Document;
use App\Models\Payment;
use App\Support\FeeBreakdown;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Generates the platform's legally-binding PDFs (spec §10.2, §10.3) with an
 * embedded QR code resolving to the public /verify route. Each document is
 * signed with an HMAC (simplified electronic signature per Law 15-04 — a real
 * ANCE certificate is a later phase) and stored on the PRIVATE documents disk.
 */
class DocumentService
{
    public function generateConditionBook(Auction $auction): Document
    {
        $auction->loadMissing(['entity', 'category', 'wilaya']);

        // NOT public: the condition book is a paid download now. Access is gated
        // in DocumentPolicy by Auction::hasBookAccess (free book or paid).
        return $this->make(
            type: DocumentType::CONDITION_BOOK,
            auction: $auction,
            userId: null,
            isPublic: false,
            title: __('documents.condition_book.title', ['auction' => $auction->localizedTitle()]),
            view: 'documents.condition-book',
            data: ['auction' => $auction],
            meta: ['book_price' => (int) $auction->book_price],
        );
    }

    public function generateAward(Auction $auction, FeeBreakdown $fees): Document
    {
        $auction->loadMissing(['entity', 'category', 'wilaya', 'winner']);
        $winner = $auction->winner;

        return $this->make(
            type: DocumentType::AWARD,
            auction: $auction,
            userId: $winner?->id,
            isPublic: false,
            title: __('documents.award.title', ['auction' => $auction->localizedTitle()]),
            view: 'documents.award',
            data: ['auction' => $auction, 'winner' => $winner, 'fees' => $fees],
            meta: [
                'winner_nin_masked' => mask_nin($winner?->nin),
                'final_price' => (int) $auction->final_price,
                'fees' => $fees->toArray(),
            ],
        );
    }

    public function generateReceipt(Payment $payment): Document
    {
        $payment->loadMissing(['auction.entity', 'user']);

        return $this->make(
            type: DocumentType::PAYMENT_RECEIPT,
            auction: $payment->auction,
            userId: $payment->user_id,
            isPublic: false,
            title: __('documents.receipt.title', ['type' => $payment->payment_type->label()]),
            view: 'documents.receipt',
            data: ['payment' => $payment],
            meta: ['payment_id' => $payment->id, 'amount' => (int) $payment->amount],
        );
    }

    public function generateDeliveryReport(Delivery $delivery): Document
    {
        $delivery->loadMissing(['auction.entity', 'user']);

        return $this->make(
            type: DocumentType::DELIVERY_REPORT,
            auction: $delivery->auction,
            userId: $delivery->user_id,
            isPublic: false,
            title: __('documents.delivery_report.title', ['auction' => $delivery->auction?->localizedTitle() ?? '']),
            view: 'documents.delivery-report',
            data: ['delivery' => $delivery],
            meta: ['delivery_id' => $delivery->id],
        );
    }

    /**
     * Full auction report (تقرير المزاد) — a signed, verifiable snapshot of every
     * detail of the auction AS OF NOW, laid out across labelled tables. Unlike the
     * award/receipt this is not a per-user document: it is an administrative record
     * the platform issues (and may re-issue any number of times) and later refers
     * to the organising entity. The meta is bound into the HMAC, so the headline
     * figures printed on the PDF are tamper-evident on /verify.
     */
    public function generateAuctionReport(Auction $auction, int $sequenceNo): Document
    {
        $auction->loadMissing([
            'entity', 'category', 'wilaya', 'commune', 'winner',
            'createdByUser', 'delivery',
        ]);

        // Hammer price for the fee table: the settled/final price once known,
        // otherwise the current highest valid bid (a live snapshot).
        $hammerPrice = (int) ($auction->final_price ?? $auction->currentPrice());
        $fees = app(FeeCalculator::class)->forAward($auction, $hammerPrice);

        // Bidding: top valid bids by amount (the ones that shaped the result),
        // each with its privacy-preserving alias — never the real bidder name.
        $bids = $auction->bids()
            ->where('is_valid', true)
            ->orderByDesc('amount')
            ->limit(15)
            ->get();

        $payments = $auction->payments()
            ->with('user')
            ->latest()
            ->get();

        // Every OTHER document issued for this auction (exclude past reports so the
        // list stays about the auction's own paperwork, not prior snapshots).
        $documents = $auction->documents()
            ->where('type', '!=', DocumentType::AUCTION_REPORT->value)
            ->latest()
            ->get();

        $appeals = $auction->appeals()->get();

        $meta = [
            'sequence_no' => $sequenceNo,
            'status' => $auction->status->value,
            'hammer_price' => $hammerPrice,
            'final_price' => $auction->final_price !== null ? (int) $auction->final_price : null,
            'bid_count' => $auction->bidCount(),
            'participant_count' => $auction->participants()->count(),
        ];

        return $this->make(
            type: DocumentType::AUCTION_REPORT,
            auction: $auction,
            userId: null,
            isPublic: false,
            title: __('auction_reports.doc_title', [
                'seq' => $sequenceNo,
                'auction' => $auction->localizedTitle(),
            ]),
            view: 'documents.auction-report',
            data: [
                'auction' => $auction,
                'fees' => $fees,
                'bids' => $bids,
                'payments' => $payments,
                'documents' => $documents,
                'appeals' => $appeals,
                'sequenceNo' => $sequenceNo,
            ],
            meta: $meta,
        );
    }

    /**
     * Re-render an already-issued document's PDF binary IN PLACE, keeping its id,
     * signature, meta, verify URL and issue date untouched — only the visual
     * rendering is refreshed from the current Blade templates. Used to repair
     * documents generated before a template fix (e.g. the RTL money reversal in
     * mpdf): the QR/signature attest the content (id + meta), not the exact bytes,
     * so re-rendering never invalidates verification.
     *
     * Returns true on success, false when the type is unsupported or a required
     * related record (payment / delivery / auction) is missing.
     */
    public function rerender(Document $document): bool
    {
        [$view, $data] = $this->reconstruct($document);

        if ($view === null) {
            return false;
        }

        $html = view($view, array_merge($data, [
            'docId' => $document->id,
            'docType' => $document->type,
            'title' => $document->title,
            'qrImage' => $this->qrDataUri((string) $document->qr_payload),
            // Reuse the exact stored verify URL so the QR keeps resolving as before.
            'verifyUrl' => $document->qr_payload,
            'signatureFingerprint' => $this->fingerprint((string) $document->signature),
            // Preserve the ORIGINAL issue date — this is a re-render, not a re-issue.
            'issuedAt' => $document->created_at,
            'logo' => $this->logoDataUri(),
            'entityName' => $document->auction?->entity?->name,
        ]))->render();

        $binary = $this->renderPdf($html);
        Storage::disk($document->diskName())->put($document->file_path, $binary);

        // Only the file size may change; leave id/signature/meta/timestamps alone.
        $document->forceFill(['file_size' => strlen($binary)])->saveQuietly();

        return true;
    }

    /**
     * Rebuild the [view, data] pair for an existing document from its stored
     * type, relations and frozen meta. Returns [null, []] for types that can't be
     * faithfully re-rendered from stored state (AUCTION_REPORT is a live snapshot;
     * re-render it via the admin module instead).
     *
     * @return array{0: ?string, 1: array<string, mixed>}
     */
    private function reconstruct(Document $document): array
    {
        return match ($document->type) {
            DocumentType::CONDITION_BOOK => (function () use ($document) {
                $auction = $document->auction;
                if (! $auction) {
                    return [null, []];
                }
                $auction->loadMissing(['entity', 'category', 'wilaya']);

                return ['documents.condition-book', ['auction' => $auction]];
            })(),

            DocumentType::AWARD => (function () use ($document) {
                $auction = $document->auction;
                if (! $auction) {
                    return [null, []];
                }
                $auction->loadMissing(['entity', 'category', 'wilaya', 'winner']);

                return ['documents.award', [
                    'auction' => $auction,
                    'winner' => $auction->winner,
                    // Rebuild from the frozen figures, never recompute.
                    'fees' => FeeBreakdown::fromArray((array) ($document->meta['fees'] ?? [])),
                ]];
            })(),

            DocumentType::PAYMENT_RECEIPT => (function () use ($document) {
                $payment = Payment::with(['auction.entity', 'user'])
                    ->find($document->meta['payment_id'] ?? null);

                return $payment ? ['documents.receipt', ['payment' => $payment]] : [null, []];
            })(),

            DocumentType::DELIVERY_REPORT => (function () use ($document) {
                $delivery = Delivery::with(['auction.entity', 'user'])
                    ->find($document->meta['delivery_id'] ?? null);

                return $delivery ? ['documents.delivery-report', ['delivery' => $delivery]] : [null, []];
            })(),

            default => [null, []],
        };
    }

    /**
     * Shared pipeline: pre-generate the UUID + signature, embed a QR pointing to
     * the verify URL, render the Blade to a PDF, store it privately, and persist
     * the Document row.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    private function make(
        DocumentType $type,
        ?Auction $auction,
        ?string $userId,
        bool $isPublic,
        string $title,
        string $view,
        array $data,
        array $meta = [],
    ): Document {
        $docId = (string) Str::uuid();
        $signature = $this->sign($docId, $type, $meta, $auction?->id);
        $verifyUrl = $this->verifyUrl($docId, $signature);
        $qrImage = $this->qrDataUri($verifyUrl);

        $html = view($view, array_merge($data, [
            'docId' => $docId,
            'docType' => $type,
            'title' => $title,
            'qrImage' => $qrImage,
            'verifyUrl' => $verifyUrl,
            // Short, human-comparable fingerprint of the full signature — printed
            // under the electronic-signature stamp so a verifier can eyeball-match
            // it against the /verify page.
            'signatureFingerprint' => $this->fingerprint($signature),
            'issuedAt' => now(),
            // Shared header (documents._layout): the platform logo and the name
            // of the organizing entity, shown above the document number.
            'logo' => $this->logoDataUri(),
            'entityName' => $auction?->entity?->name,
        ]))->render();

        $binary = $this->renderPdf($html);

        $disk = config('mazayada.documents.disk', 'documents');
        $path = $type->value.'/'.$docId.'.pdf';
        Storage::disk($disk)->put($path, $binary);

        // `id` is not mass-assignable, so set it explicitly — the QR signature is
        // bound to this exact id (HasUuids keeps a pre-set, non-empty key).
        $document = new Document([
            'auction_id' => $auction?->id,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'file_path' => $path,
            'disk' => $disk,
            'file_size' => strlen($binary),
            'mime' => 'application/pdf',
            'qr_payload' => $verifyUrl,
            'signature' => $signature,
            'is_public' => $isPublic,
            'meta' => $meta,
        ]);
        $document->id = $docId;
        $document->save();

        return $document;
    }

    /**
     * Render HTML to a PDF binary with mpdf. mpdf is used (instead of dompdf)
     * because it natively shapes/joins Arabic and handles RTL — dompdf renders
     * Arabic as disconnected glyphs. autoScriptToLang + autoLangToFont switch to
     * an Arabic-capable font with OpenType shaping for Arabic runs.
     */
    private function renderPdf(string $html): string
    {
        $tmp = storage_path('app/mpdf');
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tmp,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);
        $mpdf->SetDirectionality(locale_is_rtl() ? 'rtl' : 'ltr');
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * HMAC binding the document to its CONTENT — not just its id/type. The
     * canonical payload includes the id, type, auction and the persisted `meta`
     * (winner NIN, final price, amounts, …), so editing any signed fact in the
     * rendered PDF invalidates the QR. `meta` is persisted on the Document row,
     * so verifySignature can reproduce the exact same payload later.
     */
    public function sign(string $docId, DocumentType $type, array $meta = [], ?string $auctionId = null): string
    {
        $payload = $this->canonicalPayload([
            'id' => $docId,
            'type' => $type->value,
            'auction_id' => $auctionId,
            'meta' => $meta,
        ]);

        return hash_hmac(
            'sha256',
            $payload,
            (string) config('mazayada.documents.signing_key', config('app.key')),
        );
    }

    /** Verify a (doc, sig) pair from the QR / verify route. */
    public function verifySignature(Document $document, string $providedSig): bool
    {
        $expected = $this->sign(
            $document->id,
            $document->type,
            (array) ($document->meta ?? []),
            $document->auction_id,
        );

        return hash_equals($expected, (string) $document->signature)
            && hash_equals($expected, $providedSig);
    }

    /** First 16 hex chars of the signature — a short, comparable fingerprint. */
    public function fingerprint(string $signature): string
    {
        return strtoupper(substr($signature, 0, 16));
    }

    /**
     * Stable JSON of the signed fields: keys are recursively sorted so the byte
     * sequence is deterministic regardless of array insertion order.
     *
     * @param  array<string, mixed>  $data
     */
    private function canonicalPayload(array $data): string
    {
        $sort = function (&$value) use (&$sort): void {
            if (is_array($value)) {
                ksort($value);
                foreach ($value as &$child) {
                    $sort($child);
                }
            }
        };
        $sort($data);

        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function verifyUrl(string $docId, string $signature): string
    {
        // Production requires ZERO config: the URL is built from route() (i.e.
        // APP_URL), which on a real deployment is already the public domain — so
        // the QR just works without touching the server's .env.
        //
        // QR_VERIFICATION_BASE_URL is honoured ONLY as a local-dev override, and
        // ONLY when APP_URL points at localhost (the one case route() would emit
        // an unreachable "localhost" a phone can't open). This makes it safe to
        // hardcode a LAN IP in the local .env: it can never leak into production,
        // because there APP_URL is not localhost and the override is ignored.
        $route = route('documents.verify', ['doc' => $docId, 'sig' => $signature]);
        $override = config('mazayada.documents.qr_verification_base_url');

        $appUrlIsLocal = (bool) preg_match('#^https?://(localhost|127\.0\.0\.1)#i', (string) config('app.url'));

        if ($appUrlIsLocal && $override) {
            $url = rtrim($override, '/').'?doc='.$docId.'&sig='.$signature;
        } else {
            $url = $route;
        }

        // If we still ended up with a localhost URL (dev without a LAN override),
        // warn: a phone scanning this QR can't reach it. Never fires in production.
        if (preg_match('#https?://(localhost|127\.0\.0\.1)#i', $url)) {
            Log::warning('Document QR points to an unreachable localhost URL — set QR_VERIFICATION_BASE_URL to a LAN IP / tunnel the scanning device can reach (local dev only).', ['url' => $url]);
        }

        return $url;
    }

    /**
     * Platform logo as a base64 SVG data-URI for the document header. mpdf
     * renders an <img> with a data:image/svg+xml source (the same channel the
     * QR uses). Reads the shipped favicon.svg; returns '' if it is unreadable so
     * the header degrades to text only.
     */
    private function logoDataUri(): string
    {
        try {
            $path = public_path('favicon.svg');
            if (! is_readable($path)) {
                return '';
            }

            return 'data:image/svg+xml;base64,'.base64_encode((string) file_get_contents($path));
        } catch (\Throwable $e) {
            Log::warning('Logo embedding failed', ['error' => $e->getMessage()]);

            return '';
        }
    }

    /**
     * QR code as a base64 SVG data-URI (no imagick dependency). dompdf reliably
     * renders an <img> with a data:image/svg+xml source, but IGNORES a raw inline
     * <svg> element — so the QR must be embedded as an image, not inlined.
     * Returns '' on failure.
     */
    private function qrDataUri(string $text): string
    {
        try {
            $svg = (string) QrCode::format('svg')->size(120)->margin(0)->generate($text);

            if ($svg === '') {
                throw new \RuntimeException('empty QR output');
            }

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (\Throwable $e) {
            // A document with no QR is a broken document — surface it as an error,
            // not a silent warning. The Blade falls back to printing the verify
            // URL as text so the document is still verifiable by hand.
            Log::error('QR generation failed — document will render without a scannable code', ['error' => $e->getMessage()]);

            return '';
        }
    }
}
