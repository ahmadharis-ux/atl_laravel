<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Support\BrandLogoFetcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('brands:fetch-logos {--force : Unduh ulang walau logo sudah ada} {--only= : Batasi ke slug tertentu, dipisah koma} {--from= : Impor dari folder lokal berisi {slug}.png/.jpg}')]
#[Description('Unduh logo tiap brand dan simpan path-nya ke kolom brands.logo')]
class FetchBrandLogos extends Command
{
    public function handle(BrandLogoFetcher $fetcher): int
    {
        if ($this->option('from')) {
            return $this->importFromDirectory($fetcher, (string) $this->option('from'));
        }

        $brands = $this->brands();

        if (! $this->option('force')) {
            $brands = $brands->whereNull('logo')->values();
        }

        if ($brands->isEmpty()) {
            $this->info('Tidak ada brand yang perlu diunduh.');

            return self::SUCCESS;
        }

        $done = 0;
        $failed = [];

        $this->output->progressStart($brands->count());

        foreach ($brands as $brand) {
            if ($fetcher->download($brand)) {
                $done++;
            } else {
                $failed[] = $brand->slug;
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->info("Berhasil: {$done}. Gagal: ".count($failed).'.');

        if ($failed !== []) {
            $this->line('Belum dapat logo: '.implode(', ', $failed));
            $this->line('Tambahkan domainnya ke config/brand-logos.php (overrides) atau taruh file manual lalu jalankan --from.');
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Brand>
     */
    private function brands()
    {
        return Brand::query()
            ->when($this->option('only'), function ($query): void {
                $slugs = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));
                $query->whereIn('slug', $slugs);
            })
            ->orderBy('name')
            ->get();
    }

    private function importFromDirectory(BrandLogoFetcher $fetcher, string $directory): int
    {
        if (! is_dir($directory)) {
            $this->error("Folder tidak ditemukan: {$directory}");

            return self::FAILURE;
        }

        $done = 0;

        foreach ($this->brands() as $brand) {
            foreach (['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'] as $extension) {
                $file = rtrim($directory, '/\\').DIRECTORY_SEPARATOR.$brand->slug.'.'.$extension;

                if (is_file($file) && $fetcher->import($brand, $file)) {
                    $done++;
                    break;
                }
            }
        }

        $this->info("Logo diimpor: {$done}.");

        return self::SUCCESS;
    }
}
