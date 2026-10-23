<?php

namespace App\Support\Report;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penulis Excel (.xlsx) untuk export laporan, memakai PhpSpreadsheet.
 *
 * Satu workbook bisa berisi beberapa sheet; setiap sheet menerima kolom
 * + baris mentah. Kolom bertipe `currency` / `int` / `percent` ditulis
 * sebagai angka (bukan teks) agar tetap bisa diolah di Excel.
 */
final class ExcelReport
{
    private Spreadsheet $spreadsheet;

    public function __construct(private readonly string $title)
    {
        $this->spreadsheet = new Spreadsheet;
        $this->spreadsheet->removeSheetByIndex(0);
    }

    /**
     * Tambahkan satu sheet dari satu bagian laporan.
     *
     * @param  array<int, array{label: string, align?: string, type?: string}>  $columns
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    public function addSheet(string $name, array $columns, array $rows): void
    {
        $columns = array_values($columns);
        $rows = array_values($rows);

        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle($this->sheetName($name));

        $headers = array_map(fn (array $column): string => $column['label'], $columns);
        $sheet->fromArray($headers, null, 'A1');

        foreach ($rows as $offset => $row) {
            $excelRow = $offset + 2;

            foreach (array_values($row) as $index => $value) {
                $type = $columns[$index]['type'] ?? 'text';
                $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($index + 1).$excelRow);

                if ($value === null) {
                    $cell->setValue(null);

                    continue;
                }

                if (in_array($type, ['int', 'currency', 'percent'], true) && is_numeric($value)) {
                    $cell->setValueExplicit((float) $value, DataType::TYPE_NUMERIC);

                    continue;
                }

                $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            }
        }

        $lastRow = max(1, count($rows) + 1);
        $lastColumn = max(1, count($columns));
        $lastLetter = Coordinate::stringFromColumnIndex($lastColumn);

        foreach ($columns as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $type = $column['type'] ?? 'text';
            $align = $column['align'] ?? 'l';

            $style = [
                'alignment' => [
                    'horizontal' => $align === 'r' ? Alignment::HORIZONTAL_RIGHT : Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];

            if ($type === 'currency' || $type === 'int') {
                $style['numberFormat']['formatCode'] = '#,##0';
            } elseif ($type === 'percent') {
                $style['numberFormat']['formatCode'] = '0.0"%"';
            }

            $sheet->getStyle("{$letter}2:{$letter}{$lastRow}")->applyFromArray($style);
            $sheet->getColumnDimension($letter)->setWidth($this->columnWidth($column, $rows, $index));
        }

        $sheet->getStyle('A1:'.$lastLetter.'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '00696F']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:'.$lastLetter.$lastRow);
    }

    /**
     * Tambahkan sheet sederhana berupa pasangan label–nilai.
     *
     * @param  array<int, array{0: string, 1: scalar|null}>  $pairs
     */
    public function addPairsSheet(string $name, array $pairs): void
    {
        $this->addSheet($name, [
            ['label' => 'Metrik', 'type' => 'text'],
            ['label' => 'Nilai', 'type' => 'text'],
        ], array_map(fn (array $pair): array => [$pair[0], (string) ($pair[1] ?? '')], $pairs));
    }

    /**
     * Unduh workbook sebagai berkas .xlsx.
     */
    public function download(string $filename): StreamedResponse
    {
        $writer = new Xlsx($this->spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
            $this->spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function sheetName(string $name): string
    {
        $name = preg_replace('/[\\\\\/\*\?\:\[\]]/', ' ', $name) ?? 'Sheet';

        return mb_substr(trim($name), 0, 31);
    }

    /**
     * @param  array{label: string, type?: string}  $column
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    private function columnWidth(array $column, array $rows, int $index): float
    {
        $max = strlen($column['label']);

        foreach ($rows as $row) {
            $max = max($max, strlen((string) ($row[$index] ?? '')));
        }

        return min(40, max(10, $max + 2));
    }
}
