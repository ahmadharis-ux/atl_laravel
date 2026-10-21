<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Support\Report\CustomerReport;
use App\Support\Report\ExcelReport;
use App\Support\Report\OverviewReport;
use App\Support\Report\PdfReport;
use App\Support\Report\ProductReport;
use App\Support\Report\ProviderReport;
use App\Support\Report\ReportFormatter;
use App\Support\Report\ReportPeriod;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export seluruh laporan admin ke Excel (.xlsx) atau PDF,
 * memakai filter yang sama dengan halaman laporannya.
 */
class ExportController extends Controller
{
    public function __invoke(Request $request, string $section): StreamedResponse
    {
        $request->validate(['format' => ['required', 'in:xlsx,pdf']]);

        [$report, $title, $subtitle, $filename] = match ($section) {
            'overview' => $this->overview($request),
            'products' => $this->products($request),
            'providers' => $this->providers($request),
            'customers' => $this->customers($request),
            default => abort(404),
        };

        if ($request->string('format')->toString() === 'xlsx') {
            return $this->toExcel($report, $title, $filename);
        }

        return $this->toPdf($report, $title, $subtitle, $filename);
    }

    /**
     * @return array{0: array<string, mixed>, 1: string, 2: string, 3: string}
     */
    private function overview(Request $request): array
    {
        $period = ReportPeriod::fromRequest($request);

        return [
            (new OverviewReport($period))->build(),
            'LAPORAN RINGKASAN TRANSAKSI & LABA',
            'Periode: '.$period->label(),
            'laporan-ringkasan-'.$period->from->toDateString().'-'.$period->to->toDateString(),
        ];
    }

    /**
     * @return array{0: array<string, mixed>, 1: string, 2: string, 3: string}
     */
    private function products(Request $request): array
    {
        $period = ReportPeriod::fromRequest($request);

        return [
            (new ProductReport($period))->build(),
            'LAPORAN PRODUK',
            'Periode: '.$period->label(),
            'laporan-produk-'.$period->from->toDateString().'-'.$period->to->toDateString(),
        ];
    }

    /**
     * @return array{0: array<string, mixed>, 1: string, 2: string, 3: string}
     */
    private function providers(Request $request): array
    {
        $period = ReportPeriod::fromRequest($request);

        return [
            (new ProviderReport($period))->build(),
            'LAPORAN PROVIDER',
            'Periode: '.$period->label(),
            'laporan-provider-'.$period->from->toDateString().'-'.$period->to->toDateString(),
        ];
    }

    /**
     * @return array{0: array<string, mixed>, 1: string, 2: string, 3: string}
     */
    private function customers(Request $request): array
    {
        $report = new CustomerReport($request->only(['segment', 'q']));

        return [
            $report->build(),
            'LAPORAN PELANGGAN & CRM',
            'Data sepanjang masa (segmen relatif terhadap hari ini)',
            'laporan-pelanggan'.(($request->string('segment')->toString() !== '') ? '-'.strtolower($request->string('segment')->replace(' ', '-')->toString()) : ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function toExcel(array $report, string $title, string $filename): StreamedResponse
    {
        $excel = new ExcelReport($title);

        $excel->addPairsSheet('Ringkasan', array_map(
            fn (array $card): array => [$card['label'], $card['value']],
            $report['cards'],
        ));

        foreach ($report['sections'] as $section) {
            $excel->addSheet($section['title'], $section['columns'], $section['rows']);
        }

        return $excel->download($filename.'.xlsx');
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function toPdf(array $report, string $title, string $subtitle, string $filename): StreamedResponse
    {
        $pdf = new PdfReport($title, $subtitle);

        $pairs = [];

        foreach ($report['cards'] as $card) {
            $pairs[] = $card['label'];
            $pairs[] = $card['value'];
        }

        $pdf->pairs($pairs);

        foreach ($report['sections'] as $section) {
            $pdf->section($section['title']);

            if (! empty($section['note'])) {
                $pdf->paragraph($section['note']);
            }

            $headers = array_map(fn (array $column): string => $column['label'], $section['columns']);
            $aligns = array_map(fn (array $column): string => $column['align'] ?? 'l', $section['columns']);

            $rows = array_map(fn (array $row): array => array_map(
                fn ($cell, int $index): string => ReportFormatter::display($section['columns'][$index]['type'] ?? 'text', $cell),
                array_values($row),
                array_keys(array_values($row)),
            ), $section['rows']);

            $pdf->table($headers, $rows, $aligns);
        }

        return response()->streamDownload(fn (): int => print $pdf->render(), $filename.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
