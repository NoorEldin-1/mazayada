<?php

namespace App\Console\Commands;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Re-renders stored document PDFs in place from the current Blade templates,
 * keeping each document's id, signature, meta and issue date. Its purpose is to
 * repair documents generated before a template fix — notably the RTL money
 * reversal that mpdf produced before dzd_pdf was applied — without re-issuing
 * (the QR/signature attest the content, not the exact bytes, so they stay valid).
 *
 * AUCTION_REPORT is intentionally excluded: it is a live snapshot and is
 * regenerated from the admin auction-reports module, not re-rendered from state.
 */
class RerenderDocumentPdfs extends Command
{
    protected $signature = 'documents:rerender
        {--type= : Limit to one DocumentType (CONDITION_BOOK, AWARD, PAYMENT_RECEIPT, DELIVERY_REPORT)}
        {--id= : Re-render a single document by id}
        {--dry-run : List what would be re-rendered without writing}';

    protected $description = 'Re-render stored document PDFs in place from the current templates (repairs pre-fix files)';

    public function handle(DocumentService $documents): int
    {
        $supported = [
            DocumentType::CONDITION_BOOK->value,
            DocumentType::AWARD->value,
            DocumentType::PAYMENT_RECEIPT->value,
            DocumentType::DELIVERY_REPORT->value,
        ];

        $type = $this->option('type');
        if ($type !== null && ! in_array($type, $supported, true)) {
            $this->error("Unsupported --type: {$type}. Allowed: ".implode(', ', $supported));

            return self::FAILURE;
        }

        $query = Document::query()
            ->whereIn('type', $type !== null ? [$type] : $supported)
            ->with('auction.entity');

        if ($id = $this->option('id')) {
            $query->whereKey($id);
        }

        $total = $query->count();
        if ($total === 0) {
            $this->info('No documents matched.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info(($dryRun ? '[dry-run] ' : '')."Re-rendering {$total} document(s)…");

        $done = 0;
        $skipped = 0;
        $failed = 0;

        $query->orderBy('created_at')->chunkById(100, function ($chunk) use ($documents, $dryRun, &$done, &$skipped, &$failed) {
            foreach ($chunk as $document) {
                if ($dryRun) {
                    $this->line("  would re-render {$document->type->value} {$document->id}");
                    $done++;

                    continue;
                }

                try {
                    if ($documents->rerender($document)) {
                        $done++;
                    } else {
                        $skipped++;
                        $this->warn("  skipped {$document->type->value} {$document->id} (missing source record)");
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::error('Document re-render failed', ['id' => $document->id, 'error' => $e->getMessage()]);
                    $this->error("  failed {$document->id}: {$e->getMessage()}");
                }
            }
        });

        $this->newLine();
        $this->info("Done. Re-rendered: {$done}, skipped: {$skipped}, failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
