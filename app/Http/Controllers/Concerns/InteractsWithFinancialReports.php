<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared export helpers for the citizen + admin Financial Reports controllers:
 * CSV streaming (Excel-friendly UTF-8 BOM) and mpdf PDF rendering. mpdf mirrors
 * DocumentService::renderPdf so Arabic is shaped/joined and RTL is honoured; the
 * report PDF is transient (streamed, never persisted as a signed Document).
 */
trait InteractsWithFinancialReports
{
    /**
     * Stream the filtered transactions as a CSV download. `$transactions` is a
     * fresh, filter-applied Payment builder; it is chunked so a large export
     * never loads every row into memory.
     */
    protected function streamReportCsv(Builder $transactions, bool $showUser, string $filename): StreamedResponse
    {
        $header = [__('reports.th_date'), __('reports.th_auction')];
        if ($showUser) {
            $header[] = __('reports.th_user');
        }
        $header[] = __('reports.th_type');
        $header[] = __('reports.th_status');
        $header[] = __('reports.th_amount').' ('.__('common.currency').')';
        $header[] = __('reports.th_gateway');

        return response()->streamDownload(function () use ($transactions, $showUser, $header) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel renders Arabic correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);

            $transactions
                ->with(['auction', 'user'])
                ->orderByDesc('payments.created_at')
                ->chunk(500, function ($rows) use ($out, $showUser) {
                    foreach ($rows as $payment) {
                        $row = [
                            $payment->created_at?->format('Y-m-d H:i') ?? '',
                            $payment->auction?->localizedTitle() ?: '—',
                        ];
                        if ($showUser) {
                            $row[] = $payment->user?->name ?: '—';
                        }
                        $row[] = $payment->payment_type?->label() ?? '—';
                        $row[] = $payment->status?->label() ?? '—';
                        $row[] = dinars($payment->amount);
                        $row[] = $payment->gateway ?: '—';

                        fputcsv($out, $row);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Render a report Blade view to a downloadable PDF via mpdf.
     *
     * @param  array<string, mixed>  $data
     */
    protected function renderReportPdf(string $view, array $data, string $filename): Response
    {
        $html = view($view, $data)->render();

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
            'margin_top' => 14,
            'margin_bottom' => 14,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);
        $mpdf->SetDirectionality(locale_is_rtl() ? 'rtl' : 'ltr');
        $mpdf->WriteHTML($html);
        $binary = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
