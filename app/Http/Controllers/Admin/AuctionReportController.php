<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuctionReport;
use App\Services\AuctionReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Auction reports module (تقارير المزادات). Two audiences share this controller,
 * split exactly like AdminAppealController:
 *   - Platform admin: sees every report (EntityScope lets it through), issues new
 *     ones from the auctions table, and refers a report to its organising entity.
 *   - Organising entity (read-only): sees ONLY reports referred to it, and may
 *     only view their PDFs.
 */
class AuctionReportController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AuctionReport::class);

        // whereHas('auction') makes reports inherit per-entity isolation via the
        // auction's EntityScope (platform admin: all; entity: its own auctions).
        $query = AuctionReport::whereHas('auction')
            ->with(['auction', 'generatedBy', 'referredBy', 'document'])
            ->latest();

        $isEntity = auth()->user()->entity_id !== null;

        // An entity account only ever sees reports the platform has referred to it.
        if ($isEntity) {
            $query->referred();
        }

        $reports = $query->paginate(20);

        return view('admin.auction-reports.index', compact('reports', 'isEntity'));
    }

    /**
     * Issue a fresh report for an auction (from the auctions-table row action),
     * capturing its latest details, then stream the new PDF straight back.
     */
    public function generate(Auction $auction, AuctionReportService $reports): StreamedResponse|RedirectResponse
    {
        $this->authorize('generate', $auction);

        $report = $reports->generate($auction, auth()->user());

        // Download (attachment) so the auctions table stays put while the freshly
        // issued PDF is saved — "issue the report" hands the file straight over.
        return $this->streamReport($report, download: true)
            ?? back()->with('error', __('auction_reports.flash_generate_failed'));
    }

    /**
     * View the LATEST report issued for an auction (the "رؤية التقرير" row action).
     * Redirects back with a notice when the auction has no report yet.
     */
    public function latest(Auction $auction): StreamedResponse|RedirectResponse
    {
        $report = AuctionReport::where('auction_id', $auction->id)
            ->latest('sequence_no')
            ->first();

        if (! $report) {
            return back()->with('error', __('auction_reports.flash_no_report'));
        }

        $this->authorize('view', $report);

        return $this->streamReport($report)
            ?? back()->with('error', __('auction_reports.flash_missing_file'));
    }

    /** Stream a specific report's PDF (from the module list). */
    public function view(AuctionReport $report): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $report);

        return $this->streamReport($report)
            ?? back()->with('error', __('auction_reports.flash_missing_file'));
    }

    /** Refer a report to its organising entity (platform admin only). */
    public function refer(AuctionReport $report, AuctionReportService $reports): RedirectResponse
    {
        $this->authorize('refer', $report);

        $reports->refer($report, auth()->user());

        return back()->with('success', __('auction_reports.flash_referred'));
    }

    /**
     * Stream the report's backing PDF from the private disk, or null if the
     * document row / file is missing (callers fall back to a redirect notice).
     */
    private function streamReport(AuctionReport $report, bool $download = false): ?StreamedResponse
    {
        $document = $report->document;
        if (! $document) {
            return null;
        }

        $disk = $document->diskName();
        if (! $document->file_path || ! Storage::disk($disk)->exists($document->file_path)) {
            return null;
        }

        $name = $document->title.'.pdf';
        $headers = ['Content-Type' => $document->mime ?: 'application/pdf'];

        return $download
            ? Storage::disk($disk)->download($document->file_path, $name, $headers)
            : Storage::disk($disk)->response($document->file_path, $name, $headers);
    }
}
