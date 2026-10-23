<?php

namespace App\Support\Report;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Rentang tanggal laporan (inklusif, dibulatkan ke hari penuh).
 *
 * Default: 30 hari terakhir (hari ini termasuk). Dipakai bersama oleh
 * halaman laporan, builder laporan, dan endpoint export Excel/PDF.
 */
final class ReportPeriod
{
    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly bool $isDefault,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validate(self::rules());

        $fromInput = $validated['from'] ?? null;
        $toInput = $validated['to'] ?? null;

        $isDefault = $fromInput === null && $toInput === null;

        $to = $toInput !== null
            ? CarbonImmutable::parse($toInput)->endOfDay()
            : CarbonImmutable::now()->endOfDay();

        $from = $fromInput !== null
            ? CarbonImmutable::parse($fromInput)->startOfDay()
            : $to->subDays(29)->startOfDay();

        return new self($from, $to, $isDefault);
    }

    /**
     * Terapkan filter tanggal pada query berkolom `created_at` (atau kolom lain).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween($column, [$this->from, $this->to]);
    }

    /**
     * Jumlah hari dalam rentang (inklusif).
     */
    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to) + 1;
    }

    /**
     * Label rentang untuk ditampilkan (mis. "1 Okt 2026 – 21 Okt 2026").
     */
    public function label(): string
    {
        return $this->from->locale('id')->translatedFormat('d M Y')
            .' – '
            .$this->to->locale('id')->translatedFormat('d M Y');
    }

    /**
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return [
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
        ];
    }
}
