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

        return $this->make(
            type: DocumentType::CONDITION_BOOK,
            auction: $auction,
            userId: null,
            isPublic: true,
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
        $signature = $this->sign($docId, $type);
        $verifyUrl = $this->verifyUrl($docId, $signature);
        $qrImage = $this->qrDataUri($verifyUrl);

        $html = view($view, array_merge($data, [
            'docId' => $docId,
            'docType' => $type,
            'title' => $title,
            'qrImage' => $qrImage,
            'verifyUrl' => $verifyUrl,
            'issuedAt' => now(),
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

    /** HMAC binding the document id to its type with the platform signing key. */
    public function sign(string $docId, DocumentType $type): string
    {
        return hash_hmac(
            'sha256',
            $docId.'|'.$type->value,
            (string) config('mazayada.documents.signing_key', config('app.key')),
        );
    }

    /** Verify a (doc, sig) pair from the QR / verify route. */
    public function verifySignature(Document $document, string $providedSig): bool
    {
        $expected = $this->sign($document->id, $document->type);

        return hash_equals($expected, (string) $document->signature)
            && hash_equals($expected, $providedSig);
    }

    private function verifyUrl(string $docId, string $signature): string
    {
        $base = config('mazayada.documents.qr_verification_base_url');

        if (! $base) {
            return route('documents.verify', ['doc' => $docId, 'sig' => $signature]);
        }

        return rtrim($base, '/').'?doc='.$docId.'&sig='.$signature;
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

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (\Throwable $e) {
            Log::warning('QR generation failed', ['error' => $e->getMessage()]);

            return '';
        }
    }
}
