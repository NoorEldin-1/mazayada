<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Documents
 *
 * Streams generated PDFs (condition book / award / receipt) from the private
 * disk. Access is gated by DocumentPolicy@download (public docs to any
 * authenticated user; private docs to the owner / auction winner / entity staff).
 */
class DocumentController extends ApiController
{
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
