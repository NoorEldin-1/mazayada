<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Document;
use App\Services\DocumentLibraryService;
use App\Support\DocumentFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Documents
 *
 * The signed-in user's personal document library — every generated PDF tied to an
 * auction they engaged with (condition book / award / receipt / delivery report),
 * plus the policy-gated binary download. Streams from the private disk; access is
 * gated by DocumentPolicy@download.
 */
class DocumentController extends ApiController
{
    /**
     * List my documents
     *
     * The user's documents with the same advanced filters as the web library.
     * Every returned document is one the user may download.
     *
     * @queryParam search string Match auction title or document name. Example: villa
     * @queryParam type string[] Document types: CONDITION_BOOK, AWARD, PAYMENT_RECEIPT, DELIVERY_REPORT.
     * @queryParam preset string Date preset: today, 7d, 30d, this_month, this_year, all. Example: 30d
     * @queryParam from date Custom range start (Y-m-d). Example: 2026-01-01
     * @queryParam to date Custom range end (Y-m-d). Example: 2026-12-31
     * @queryParam category_id integer Filter by auction category. Example: 3
     * @queryParam wilaya_id integer Filter by auction wilaya. Example: 16
     * @queryParam entity_id string Filter by organizing entity.
     * @queryParam sort string One of recent, oldest, auction (default recent). Example: recent
     */
    public function index(Request $request, DocumentLibraryService $service): JsonResponse
    {
        $filters = DocumentFilters::fromRequest($request);

        $documents = $service->query($request->user(), $filters)
            ->paginate((int) min(50, max(1, (int) $request->query('per_page', 24))));

        return $this->paginated($documents, DocumentResource::class);
    }

    /**
     * My documents summary
     *
     * Per-type counts and total size — the stat tiles for the mobile library.
     */
    public function summary(Request $request, DocumentLibraryService $service): JsonResponse
    {
        return $this->ok($service->stats($request->user()));
    }

    /**
     * Download a document
     *
     * Streams the document binary (application/pdf).
     */
    public function download(Document $document): StreamedResponse
    {
        $this->authorize('download', $document);

        $disk = $document->diskName();
        abort_unless($document->file_path && Storage::disk($disk)->exists($document->file_path), 404);

        return Storage::disk($disk)->response(
            $document->file_path,
            $document->title.'.pdf',
            ['Content-Type' => $document->mime ?: 'application/pdf'],
        );
    }
}
