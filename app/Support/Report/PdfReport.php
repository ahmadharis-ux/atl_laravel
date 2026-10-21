<?php

namespace App\Support\Report;

/**
 * Penulis PDF minimal (A4, font bawaan Courier) untuk export laporan.
 *
 * Sengaja dibuat tanpa library tambahan: cukup untuk tabel laporan
 * multi-halaman (header diulang, nomor halaman, rata kanan/kiara).
 * Teks disaring ke ASCII agar aman dengan encoding WinAnsi.
 */
final class PdfReport
{
    private const PAGE_WIDTH = 595.28;

    private const PAGE_HEIGHT = 841.89;

    private const MARGIN = 40.0;

    private const CONTENT_WIDTH = self::PAGE_WIDTH - (2 * self::MARGIN);

    private const FONT_SIZE = 8.0;

    private const LINE_HEIGHT = 11.0;

    private const CHAR_WIDTH = 0.6;

    /**
     * Kapasitas karakter per baris tabel (Courier 8pt).
     */
    private const MAX_CHARS = 107;

    /** @var array<int, string> */
    private array $pages = [];

    private string $buffer = '';

    private float $y = 0.0;

    public function __construct(
        private readonly string $title,
        private readonly string $subtitle = '',
    ) {
        $this->newPage();
        $this->text($this->title, 13.0, true);
        $this->text('Dicetak '.now()->format('d/m/Y H:i'), 8.0, false);

        if ($this->subtitle !== '') {
            $this->text($this->subtitle, 8.0, false);
        }

        $this->space(6.0);
    }

    /**
     * Judul bagian (bold).
     */
    public function section(string $title): void
    {
        $this->ensure(3.0 * self::LINE_HEIGHT);
        $this->space(4.0);
        $this->text($title, 10.0, true);
        $this->space(2.0);
    }

    /**
     * Paragraf biasa (auto-wrap).
     */
    public function paragraph(string $text): void
    {
        $width = self::maxCharsFor(self::FONT_SIZE);

        foreach (explode("\n", wordwrap($this->sanitize($text), $width)) as $line) {
            $this->ensure(self::LINE_HEIGHT);
            $this->raw($line, self::FONT_SIZE, false, self::MARGIN);
        }

        $this->space(3.0);
    }

    /**
     * Baris berisi "Label: nilai".
     *
     * @param  array<int, string>  $pairs  [label, value, label, value, ...]
     */
    public function pairs(array $pairs): void
    {
        $half = intdiv(self::MAX_CHARS, 2);

        foreach (array_chunk($pairs, 2) as $chunk) {
            $left = $chunk[0] ?? '';
            $right = $chunk[1] ?? '';
            $line = str_pad($this->sanitize($left), (int) ($half * 0.55)).$this->sanitize($right);

            $this->ensure(self::LINE_HEIGHT);
            $this->raw($line, self::FONT_SIZE, false, self::MARGIN);
        }

        $this->space(3.0);
    }

    /**
     * Tabel data dengan header tebal; header diulang saat pindah halaman.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, scalar|null>>  $rows
     * @param  array<int, string>  $aligns  'l' | 'r' per kolom
     */
    public function table(array $headers, array $rows, array $aligns = []): void
    {
        $headers = array_map($this->sanitize(...), array_values($headers));
        $rows = array_map(
            fn (array $row): array => array_map(
                fn ($cell): string => $this->sanitize($cell === null ? '' : (string) $cell),
                array_values($row),
            ),
            $rows,
        );

        $count = count($headers);

        if ($count === 0) {
            return;
        }

        $aligns = array_pad(array_values($aligns), $count, 'l');
        $widths = $this->columnWidths($headers, $rows);

        $this->ensure(3.0 * self::LINE_HEIGHT);
        $this->tableRow($headers, $widths, $aligns, true);
        $this->rule();

        foreach ($rows as $row) {
            if ($this->y < self::MARGIN + (2.5 * self::LINE_HEIGHT)) {
                $this->newPage();
                $this->tableRow($headers, $widths, $aligns, true);
                $this->rule();
            }

            $this->tableRow(array_pad($row, $count, ''), $widths, $aligns, false);
        }

        $this->space(8.0);
    }

    /**
     * Render seluruh dokumen menjadi string PDF.
     */
    public function render(): string
    {
        $this->flush();

        $total = count($this->pages);

        foreach ($this->pages as $index => $content) {
            $footer = 'Halaman '.($index + 1).' / '.$total;
            $x = self::PAGE_WIDTH - self::MARGIN - (strlen($footer) * self::CHAR_WIDTH * 7.0);

            $this->pages[$index] = $content.$this->textOp($footer, 7.0, false, $x, self::MARGIN - 12.0);
        }

        return $this->buildPdf();
    }

    private function tableRow(array $cells, array $widths, array $aligns, bool $header): void
    {
        $size = $header ? 7.5 : self::FONT_SIZE;
        $x = self::MARGIN;
        $baseline = $this->y - $size;

        if ($header) {
            $this->buffer .= sprintf(
                "0.94 g %.2F %.2F %.2F %.2F re f 0 g\n",
                self::MARGIN - 2,
                $this->y - self::LINE_HEIGHT + 2,
                self::CONTENT_WIDTH + 4,
                self::LINE_HEIGHT,
            );
        }

        foreach ($cells as $index => $cell) {
            $width = $widths[$index] ?? 0;
            $text = $this->truncate($cell, $width);
            $textX = $aligns[$index] === 'r'
                ? $x + (($width - strlen($text)) * self::CHAR_WIDTH * $size)
                : $x;

            $this->buffer .= $this->textOp($text, $size, $header, $textX, $baseline);
            $x += $width * self::CHAR_WIDTH * self::FONT_SIZE;
        }

        $this->y -= self::LINE_HEIGHT;
        $this->buffer .= sprintf("0.88 G %.2F %.2F m %.2F %.2F l S 0 g\n", self::MARGIN, $this->y + 3, self::PAGE_WIDTH - self::MARGIN, $this->y + 3);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, int>
     */
    private function columnWidths(array $headers, array $rows): array
    {
        $count = count($headers);
        $lengths = [];

        for ($i = 0; $i < $count; $i++) {
            $lengths[$i] = strlen($headers[$i]);
        }

        foreach ($rows as $row) {
            for ($i = 0; $i < $count; $i++) {
                $lengths[$i] = max($lengths[$i] ?? 0, strlen($row[$i] ?? ''));
            }
        }

        $total = array_sum($lengths);

        if ($total > self::MAX_CHARS) {
            $scale = (self::MAX_CHARS - (5 * $count)) / max(1, $total - (5 * $count));
            $lengths = array_map(fn (int $length): int => max(5, (int) floor($length * $scale)), $lengths);

            while (array_sum($lengths) > self::MAX_CHARS) {
                $index = array_keys($lengths, max($lengths))[0];

                if ($lengths[$index] <= 5) {
                    break;
                }

                $lengths[$index]--;
            }
        }

        return $lengths;
    }

    private function text(string $string, float $size, bool $bold): void
    {
        $this->ensure($size * 1.5);
        $this->raw($string, $size, $bold, self::MARGIN);
    }

    private function raw(string $string, float $size, bool $bold, float $x): void
    {
        $this->buffer .= $this->textOp($this->sanitize($string), $size, $bold, $x, $this->y - $size);
        $this->y -= $size * 1.45;
    }

    private function textOp(string $string, float $size, bool $bold, float $x, float $y): string
    {
        $font = $bold ? 'F2' : 'F1';

        return sprintf("BT /%s %.1F Tf %.2F %.2F Tm (%s) Tj ET\n", $font, $size, $x, $y, $this->escape($string));
    }

    private function rule(): void
    {
        $this->buffer .= sprintf("0.6 G %.2F %.2F m %.2F %.2F l S 0 g\n", self::MARGIN, $this->y + 1, self::PAGE_WIDTH - self::MARGIN, $this->y + 1);
    }

    private function space(float $height): void
    {
        $this->y -= $height;
    }

    private function ensure(float $height): void
    {
        if ($this->y - $height < self::MARGIN + 20) {
            $this->newPage();
        }
    }

    private function newPage(): void
    {
        $this->flush();
        $this->buffer = '';
        $this->y = self::PAGE_HEIGHT - self::MARGIN;
    }

    private function flush(): void
    {
        if ($this->buffer !== '') {
            $this->pages[] = $this->buffer;
            $this->buffer = '';
        }
    }

    private function buildPdf(): string
    {
        $pageCount = count($this->pages);
        $objects = [];

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold /Encoding /WinAnsiEncoding >>';

        $kids = [];

        for ($i = 0; $i < $pageCount; $i++) {
            $pageId = 5 + ($i * 2);
            $contentId = $pageId + 1;
            $kids[] = "{$pageId} 0 R";

            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                $contentId,
            );

            $stream = $this->pages[$i];
            $objects[$contentId] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream.'endstream';
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$pageCount.' >>';

        ksort($objects);

        $lastId = max(array_keys($objects));
        $output = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($output);
            $output .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xref = "xref\n0 ".($lastId + 1)."\n0000000000 65535 f \n";

        for ($id = 1; $id <= $lastId; $id++) {
            $xref .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        return $output
            .$xref
            ."trailer\n<< /Size ".($lastId + 1)." /Root 1 0 R >>\nstartxref\n"
            .strlen($output)
            ."\n%%EOF\n";
    }

    private function sanitize(mixed $value): string
    {
        $string = (string) $value;

        if (! mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);

        if ($ascii !== false) {
            $string = $ascii;
        }

        return preg_replace('/[^\x20-\x7E]/', '', $string) ?? '';
    }

    private function escape(string $string): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $string);
    }

    private function truncate(string $string, int $width): string
    {
        if (strlen($string) <= $width) {
            return $string;
        }

        if ($width <= 2) {
            return substr($string, 0, $width);
        }

        return substr($string, 0, $width - 2).'..';
    }

    private static function maxCharsFor(float $size): int
    {
        return (int) floor(self::CONTENT_WIDTH / (self::CHAR_WIDTH * $size));
    }
}
