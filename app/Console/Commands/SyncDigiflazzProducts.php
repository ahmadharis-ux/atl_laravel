<?php

namespace App\Console\Commands;

use App\Enums\ProductPaymentType;
use App\Support\Digiflazz;
use App\Support\DigiflazzException;
use App\Support\DigiflazzProductSync;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('digiflazz:sync-products {--pasca : Sinkron juga produk pascabayar}')]
#[Description('Impor daftar harga produk dari Digiflazz ke katalog lokal')]
class SyncDigiflazzProducts extends Command
{
    public function handle(): int
    {
        $sync = new DigiflazzProductSync(Digiflazz::make());

        try {
            $this->info('Mengambil daftar harga prabayar...');
            $prepaid = $sync->sync(ProductPaymentType::Prabayar);
            $this->info("Prabayar: {$prepaid} produk tersinkron.");

            if ($this->option('pasca')) {
                $this->info('Mengambil daftar harga pascabayar...');
                $postpaid = $sync->sync(ProductPaymentType::Pascabayar);
                $this->info("Pascabayar: {$postpaid} produk tersinkron.");
            }
        } catch (DigiflazzException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
