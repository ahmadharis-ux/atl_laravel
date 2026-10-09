<?php

namespace App\Http\Controllers\Admin\CustomerTopup;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Daftar produk topup (data hardcode sementara).
     */
    public function index(): View
    {
        $products = [
            ['code' => 'ML-86', 'brand' => 'Mobile Legends', 'category' => 'Games', 'name' => 'Mobile Legends 86 Diamonds', 'detail' => 'Instant delivery via User ID', 'markup' => 6, 'product_price' => 20300, 'selling_price' => 21500, 'status' => 'Aktif', 'provider' => 'DiamondStore'],
            ['code' => 'ML-172', 'brand' => 'Mobile Legends', 'category' => 'Games', 'name' => 'Mobile Legends 172 Diamonds', 'detail' => 'Instant delivery via User ID', 'markup' => 6, 'product_price' => 41000, 'selling_price' => 43500, 'status' => 'Aktif', 'provider' => 'DiamondStore'],
            ['code' => 'FF-70', 'brand' => 'Free Fire', 'category' => 'Games', 'name' => 'Free Fire 70 Diamonds', 'detail' => 'Top up by Player ID', 'markup' => 5, 'product_price' => 9500, 'selling_price' => 10000, 'status' => 'Aktif', 'provider' => 'GarenaAPI'],
            ['code' => 'FF-140', 'brand' => 'Free Fire', 'category' => 'Games', 'name' => 'Free Fire 140 Diamonds', 'detail' => 'Top up by Player ID', 'markup' => 5, 'product_price' => 18600, 'selling_price' => 19500, 'status' => 'Aktif', 'provider' => 'GarenaAPI'],
            ['code' => 'PUBG-60', 'brand' => 'PUBG Mobile', 'category' => 'Games', 'name' => 'PUBG Mobile 60 UC', 'detail' => 'UC via Character ID', 'markup' => 7, 'product_price' => 14000, 'selling_price' => 15000, 'status' => 'Aktif', 'provider' => 'UniPin'],
            ['code' => 'TSEL-50', 'brand' => 'Telkomsel', 'category' => 'Pulsa', 'name' => 'Pulsa Telkomsel 50.000', 'detail' => 'Denom 50.000, masa aktif +30 hari', 'markup' => 4, 'product_price' => 48000, 'selling_price' => 50000, 'status' => 'Aktif', 'provider' => 'Digiflazz'],
            ['code' => 'ISAT-25', 'brand' => 'Indosat', 'category' => 'Pulsa', 'name' => 'Pulsa Indosat 25.000', 'detail' => 'Denom 25.000, masa aktif +30 hari', 'markup' => 4, 'product_price' => 24000, 'selling_price' => 25000, 'status' => 'Aktif', 'provider' => 'Digiflazz'],
            ['code' => 'GP-50', 'brand' => 'Google Play', 'category' => 'Voucher', 'name' => 'Google Play Gift Card 50.000', 'detail' => 'Kode voucher region ID', 'markup' => 5, 'product_price' => 47500, 'selling_price' => 50000, 'status' => 'Nonaktif', 'provider' => 'Codashop'],
            ['code' => 'GI-300', 'brand' => 'Genshin Impact', 'category' => 'Games', 'name' => 'Genshin Impact 300 Genesis', 'detail' => 'Genesis Crystals via UID', 'markup' => 6, 'product_price' => 70700, 'selling_price' => 75000, 'status' => 'Aktif', 'provider' => 'Moogold'],
            ['code' => 'VAL-420', 'brand' => 'Valorant', 'category' => 'Games', 'name' => 'Valorant 420 Points', 'detail' => 'VP via Riot ID', 'markup' => 8, 'product_price' => 41700, 'selling_price' => 45000, 'status' => 'Aktif', 'provider' => 'SmileOne'],
            ['code' => 'STEAM-60', 'brand' => 'Steam', 'category' => 'Voucher', 'name' => 'Steam Wallet 60.000', 'detail' => 'Kode wallet region ID', 'markup' => 5, 'product_price' => 57000, 'selling_price' => 60000, 'status' => 'Aktif', 'provider' => 'Codashop'],
            ['code' => 'COC-500', 'brand' => 'Clash of Clans', 'category' => 'Games', 'name' => 'Clash of Clans 500 Gems', 'detail' => 'Gems via Player Tag', 'markup' => 5, 'product_price' => 61900, 'selling_price' => 65000, 'status' => 'Nonaktif', 'provider' => 'Codashop'],
        ];

        return view('admin.customer-topup.product', [
            'products' => $products,
            'categories' => collect($products)->pluck('category')->unique()->values()->all(),
            'brands' => collect($products)->pluck('brand')->unique()->values()->all(),
            'statusTones' => [
                'Aktif' => 'bg-mint-l tx-mintt',
                'Nonaktif' => 'bg-errc-l tx-err',
            ],
        ]);
    }
}
