<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ProductRegion;
use Illuminate\Console\Command;

class BackfillProductRegion extends Command
{
    protected $signature = 'products:backfill-region';

    protected $description = 'Parse region dari nama produk untuk semua produk yang sudah ada (satu kali setelah deploy).';

    public function handle(): int
    {
        $updated = 0;

        Product::query()
            ->whereNull('region')
            ->chunkById(200, function ($products) use (&$updated): void {
                foreach ($products as $product) {
                    $region = ProductRegion::parse((string) $product->name);

                    if ($region !== null) {
                        $product->forceFill(['region' => $region])->save();
                        $updated++;
                    }
                }
            });

        $this->info("Backfill selesai. {$updated} produk diperbarui region-nya.");

        return self::SUCCESS;
    }
}
