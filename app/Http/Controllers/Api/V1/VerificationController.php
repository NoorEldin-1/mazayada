<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group System
 *
 * Public authenticity check for a platform-issued document.
 */
class VerificationController extends ApiController
{
    /**
     * Verify a document
     *
     * The JSON twin of the web `/verify` page, so the app's QR scanner can render
     * the result natively instead of opening a browser. The QR printed on every
     * document encodes the web URL; take its `doc` and `sig` query parameters and
     * call this endpoint with them.
     *
     * Deliberately public and deliberately minimal: it answers "was this paper
     * issued by the platform and is it unaltered?" and returns only what a holder
     * can already read off the sheet (type, auction title, issue date, amount) plus
     * a short signature fingerprint to compare. Never any personal data, and never
     * the document file — downloading still requires an authorised session.
     *
     * An invalid or unknown pair returns HTTP 200 with `valid: false` (not a 404):
     * failing to verify IS the answer, and an error status would be indistinguishable
     * from a network problem.
     *
     * @unauthenticated
     *
     * @queryParam doc string required The document id from the QR code. Example: 9b1f...
     * @queryParam sig string required The signature from the QR code. Example: 4f3c...
     *
     * @response 200 {"data":{"valid":false,"document":null},"message":null,"meta":{}}
     */
    public function verify(Request $request, DocumentService $documents): JsonResponse
    {
        $docId = (string) $request->query('doc', '');
        $sig = (string) $request->query('sig', '');

        $document = $docId !== '' ? Document::with('auction.entity')->find($docId) : null;
        $valid = $document !== null && $sig !== '' && $documents->verifySignature($document, $sig);

        if (! $valid) {
            return $this->ok(['valid' => false, 'document' => null]);
        }

        $meta = (array) ($document->meta ?? []);
        $amount = $meta['final_price'] ?? $meta['amount'] ?? null;

        return $this->ok([
            'valid' => true,
            'document' => [
                'id' => $document->id,
                'type' => $document->type?->value,
                'type_label' => $document->type?->label(),
                'title' => $document->title,
                'issued_at' => $document->created_at?->toIso8601String(),
                'auction_title' => $document->auction?->localizedTitle(),
                'entity_name' => $document->auction?->entity?->name,
                // Money is in dinars, like everywhere else in the API.
                'amount' => $amount !== null ? dinars((int) $amount) : null,
                'amount_formatted' => $amount !== null ? dzd((int) $amount) : null,
                // Short hash of the full signature — printed on the document so the
                // holder can eyeball that this is the same paper.
                'fingerprint' => $documents->fingerprint((string) $document->signature),
            ],
        ]);
    }
}
