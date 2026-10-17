<?php

namespace App\Support;

use App\Models\Brand;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mengunduh logo brand dari sumber otomatis, menyimpannya ke disk, dan
 * mencatat path-nya di kolom `brands.logo`.
 *
 * Domain resmi ditebak dari nama brand (Clearbit autocomplete) dengan skor
 * kemiripan nama, atau dari peta kurasi di config/brand-logos.php untuk brand
 * yang domainnya sudah pasti.
 */
final class BrandLogoFetcher
{
    /**
     * Tentukan domain logo brand (kurasi lebih dulu, lalu otomatis).
     */
    public function resolveDomain(Brand $brand): ?string
    {
        $override = config('brand-logos.overrides.'.$brand->slug);

        if (is_string($override) && $override !== '') {
            return $override;
        }

        try {
            $response = Http::acceptJson()
                ->timeout($this->timeout())
                ->get((string) config('brand-logos.resolve_endpoint'), ['query' => $brand->name]);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $candidates = $response->json();

        if (! is_array($candidates)) {
            return null;
        }

        $best = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }

            $domain = $candidate['domain'] ?? null;
            $name = (string) ($candidate['name'] ?? '');

            if (! is_string($domain) || $domain === '') {
                continue;
            }

            $score = $this->score($brand->name, $name);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $domain;
            }
        }

        return $bestScore >= (int) config('brand-logos.score_threshold', 60) ? $best : null;
    }

    /**
     * Unduh dan simpan logo brand. Mengembalikan true bila berhasil.
     */
    public function download(Brand $brand): bool
    {
        $domain = $this->resolveDomain($brand);

        if ($domain === null) {
            return false;
        }

        foreach ((array) config('brand-logos.logo_endpoints', []) as $template) {
            $url = str_replace('{domain}', $domain, (string) $template);
            $bytes = $this->request($url);

            if ($bytes === null) {
                continue;
            }

            $extension = $this->extensionFor($bytes);

            if ($extension === null) {
                continue;
            }

            $this->store($brand, $bytes, $extension);

            return true;
        }

        return false;
    }

    /**
     * Ambil isi gambar dari URL; null bila gagal / bukan gambar layak.
     */
    private function request(string $url): ?string
    {
        try {
            $response = Http::timeout($this->timeout())->get($url);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $bytes = $response->body();

        if (strlen($bytes) < (int) config('brand-logos.min_bytes', 200)) {
            return null;
        }

        return $bytes;
    }

    /**
     * Impor logo dari file lokal (mis. hasil kurasi manual).
     */
    public function import(Brand $brand, string $sourcePath): bool
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            return false;
        }

        $bytes = (string) file_get_contents($sourcePath);
        $extension = $this->extensionFor($bytes) ?? Str::lower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        if (! in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'], true)) {
            return false;
        }

        $this->store($brand, $bytes, $extension === 'jpeg' ? 'jpg' : $extension);

        return true;
    }

    /**
     * Tulis berkas ke disk dan perbarui kolom logo brand. Gambar raster
     * (png/jpg/gif/webp) diperkecil dan diubah ke WebP; SVG/ICO dibiarkan.
     */
    public function store(Brand $brand, string $bytes, string $extension): string
    {
        $disk = (string) config('brand-logos.disk', 'brands');

        if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            $webp = ImageProcessor::toWebp($bytes, 400, 400);

            if ($webp !== null) {
                $bytes = $webp;
                $extension = 'webp';
            }
        }

        $path = $brand->slug.'.'.$extension;

        if ($brand->logo !== null && $brand->logo !== $path) {
            Storage::disk($disk)->delete($brand->logo);
        }

        Storage::disk($disk)->put($path, $bytes);

        $brand->forceFill(['logo' => $path])->save();

        return $path;
    }

    /**
     * Tebak ekstensi dari isi berkas gambar.
     */
    private function extensionFor(string $bytes): ?string
    {
        if (str_contains(Str::lower(substr($bytes, 0, 300)), '<svg')) {
            return 'svg';
        }

        $info = @getimagesizefromstring($bytes);

        if ($info === false || ! isset($info['mime'])) {
            return null;
        }

        return match ($info['mime']) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/vnd.microsoft.icon', 'image/x-icon' => 'ico',
            default => null,
        };
    }

    private function timeout(): int
    {
        return (int) config('brand-logos.timeout', 20);
    }

    /**
     * Skor kemiripan nama brand dengan kandidat (0-100).
     */
    private function score(string $brand, string $candidate): int
    {
        $brand = $this->normalize($brand);
        $candidate = $this->normalize($candidate);

        if ($brand === '' || $candidate === '') {
            return 0;
        }

        if ($brand === $candidate) {
            return 100;
        }

        if (str_starts_with($candidate, $brand)) {
            return 70;
        }

        foreach (explode(' ', $brand) as $token) {
            if ($token !== '' && ! str_contains($candidate, $token)) {
                return 0;
            }
        }

        return 60;
    }

    private function normalize(string $value): string
    {
        $value = Str::lower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }
}
