<?php

namespace Database\Seeders;

use App\Enums\MarkupType;
use App\Enums\ProductPaymentType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Type;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed data contoh produk prabayar & pascabayar.
     * Struktur ini juga menjadi acuan saat sync Digiflazz nanti.
     */
    public function run(): void
    {
        $brands = [
            'XoQr5D' => 'Tanaka',
            'br-ml' => 'Mobile Legends',
            'br-ff' => 'Free Fire',
            'br-tsel' => 'Telkomsel',
            'br-gp' => 'Google Play',
            'br-pln' => 'PLN',
            'br-pdam' => 'PDAM',
        ];
        foreach ($brands as $external => $name) {
            Brand::updateOrCreate(['name' => $name], ['external_id' => $external, 'slug' => str($name)->slug()]);
        }

        $categories = [
            'MgvpDK' => 'Hiburan',
            'cat-games' => 'Games',
            'cat-pulsa' => 'Pulsa',
            'cat-voucher' => 'Voucher',
            'cat-tagihan' => 'Tagihan',
        ];
        foreach ($categories as $external => $name) {
            Category::updateOrCreate(['name' => $name], ['external_id' => $external, 'slug' => str($name)->slug()]);
        }

        $types = [
            'yDbnzD' => 'Denom',
            'ty-voucher' => 'Voucher',
            'ty-tagihan' => 'Tagihan',
        ];
        foreach ($types as $external => $name) {
            Type::updateOrCreate(['name' => $name], ['external_id' => $external, 'slug' => str($name)->slug()]);
        }

        $sellers = ['Digiflazz', 'DiamondStore', 'GarenaAPI', 'UniPin', 'Codashop'];
        foreach ($sellers as $name) {
            Seller::updateOrCreate(['name' => $name], ['connection_type' => 'api']);
        }

        $brand = fn (string $name) => Brand::where('name', $name)->value('id');
        $category = fn (string $name) => Category::where('name', $name)->value('id');
        $type = fn (string $name) => Type::where('name', $name)->value('id');
        $seller = fn (string $name) => Seller::where('name', $name)->value('id');

        $products = [
            ['code' => 'pre30536375', 'name' => 'Tanaka HD Paket HBO 1 Bulan', 'payment_type' => ProductPaymentType::Prabayar, 'brand' => 'Tanaka', 'category' => 'Hiburan', 'type' => 'Voucher', 'seller' => 'Digiflazz', 'cost_price' => 68746, 'markup_type' => MarkupType::Fixed, 'markup_value' => 1254, 'stock' => 0, 'unlimited_stock' => true, 'status' => true, 'sold_count' => 30],
            ['code' => 'ML-86', 'name' => 'Mobile Legends 86 Diamonds', 'payment_type' => ProductPaymentType::Prabayar, 'brand' => 'Mobile Legends', 'category' => 'Games', 'type' => 'Voucher', 'seller' => 'DiamondStore', 'cost_price' => 20300, 'markup_type' => MarkupType::Percent, 'markup_value' => 6, 'stock' => 120, 'unlimited_stock' => false, 'status' => true, 'sold_count' => 540],
            ['code' => 'ML-172', 'name' => 'Mobile Legends 172 Diamonds', 'payment_type' => ProductPaymentType::Prabayar, 'brand' => 'Mobile Legends', 'category' => 'Games', 'type' => 'Voucher', 'seller' => 'DiamondStore', 'cost_price' => 41000, 'markup_type' => MarkupType::Percent, 'markup_value' => 6, 'stock' => 80, 'unlimited_stock' => false, 'status' => true, 'sold_count' => 210],
            ['code' => 'FF-70', 'name' => 'Free Fire 70 Diamonds', 'payment_type' => ProductPaymentType::Prabayar, 'brand' => 'Free Fire', 'category' => 'Games', 'type' => 'Voucher', 'seller' => 'GarenaAPI', 'cost_price' => 9500, 'markup_type' => MarkupType::Percent, 'markup_value' => 5, 'stock' => 200, 'unlimited_stock' => false, 'status' => true, 'sold_count' => 380],
            ['code' => 'TSEL-50', 'name' => 'Pulsa Telkomsel 50.000', 'payment_type' => ProductPaymentType::Prabayar, 'brand' => 'Telkomsel', 'category' => 'Pulsa', 'type' => 'Denom', 'seller' => 'Digiflazz', 'cost_price' => 48000, 'markup_type' => MarkupType::Fixed, 'markup_value' => 2000, 'stock' => 0, 'unlimited_stock' => true, 'status' => true, 'sold_count' => 720],
            ['code' => 'GP-50', 'name' => 'Google Play Gift Card 50.000', 'payment_type' => ProductPaymentType::Prabayar, 'brand' => 'Google Play', 'category' => 'Voucher', 'type' => 'Voucher', 'seller' => 'Codashop', 'cost_price' => 47500, 'markup_type' => MarkupType::Percent, 'markup_value' => 5, 'stock' => 45, 'unlimited_stock' => false, 'status' => false, 'sold_count' => 25],
            ['code' => 'PLN-PASCA', 'name' => 'Tagihan PLN Pascabayar', 'payment_type' => ProductPaymentType::Pascabayar, 'brand' => 'PLN', 'category' => 'Tagihan', 'type' => 'Tagihan', 'seller' => 'Digiflazz', 'cost_price' => 0, 'markup_type' => MarkupType::Fixed, 'markup_value' => 2500, 'stock' => 0, 'unlimited_stock' => true, 'status' => true, 'sold_count' => 160],
            ['code' => 'PDAM-PASCA', 'name' => 'Tagihan PDAM', 'payment_type' => ProductPaymentType::Pascabayar, 'brand' => 'PDAM', 'category' => 'Tagihan', 'type' => 'Tagihan', 'seller' => 'Digiflazz', 'cost_price' => 0, 'markup_type' => MarkupType::Fixed, 'markup_value' => 2500, 'stock' => 0, 'unlimited_stock' => true, 'status' => true, 'sold_count' => 95],
        ];

        foreach ($products as $data) {
            Product::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'payment_type' => $data['payment_type'],
                    'brand_id' => $brand($data['brand']),
                    'category_id' => $category($data['category']),
                    'type_id' => $type($data['type']),
                    'seller_id' => $seller($data['seller']),
                    'cost_price' => $data['cost_price'],
                    'markup_type' => $data['markup_type'],
                    'markup_value' => $data['markup_value'],
                    'stock' => $data['stock'],
                    'unlimited_stock' => $data['unlimited_stock'],
                    'status' => $data['status'],
                    'sold_count' => $data['sold_count'],
                ],
            );
        }
    }
}
