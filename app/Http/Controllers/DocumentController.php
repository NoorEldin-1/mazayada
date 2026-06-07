<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Stream a generated document from the private disk (policy-gated). Mirrors
     * the KYC document streaming pattern.
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

    /**
     * Public QR verification (spec §9.3). Validates the HMAC signature and shows
     * an authenticity page with a masked summary only — never PII or the PDF.
     */
    public function verify(Request $request, DocumentService $documents): View
    {
        $docId = (string) $request->query('doc');
        $sig = (string) $request->query('sig');

        $document = $docId ? Document::with('auction')->find($docId) : null;
        $valid = $document !== null && $sig !== '' && $documents->verifySignature($document, $sig);

        return view('documents.verify', [
            'valid' => $valid,
            'document' => $valid ? $document : null,
        ]);
    }
}
