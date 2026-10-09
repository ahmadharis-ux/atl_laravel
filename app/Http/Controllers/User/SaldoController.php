<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Support\Wallet;
use Illuminate\View\View;

class SaldoController extends Controller
{
    public function index(): View
    {
        return view('user.saldo.index', [
            'balance' => Wallet::BALANCE,
            'points' => Wallet::POINTS,
            'adminFee' => Wallet::ADMIN_FEE,
            'shortcuts' => [
                ['label' => 'Transfer', 'icon' => 'swap_horiz',    'tone' => 'bg-tealc-m tx-tealt'],
                ['label' => 'Poin',     'icon' => 'loyalty',       'tone' => 'bg-gold tx-gold'],
                ['label' => 'Kontak',   'icon' => 'support_agent', 'tone' => 'bg-sky-l tx-navy'],
                ['label' => 'Riwayat',  'icon' => 'history',       'tone' => 'bg-mint-l tx-mintt'],
            ],
            'transactions' => [
                ['icon' => 'phone_android',  'title' => 'Pulsa Telkomsel 25.000',    'detail' => '081234567801 • 04 Okt 10:42', 'amount' => 26500,  'status' => 'Sukses'],
                ['icon' => 'wifi',           'title' => 'Paket Data XL 15GB',        'detail' => '087788990011 • 04 Okt 09:15', 'amount' => 58000,  'status' => 'Sukses'],
                ['icon' => 'electric_bolt',  'title' => 'Token PLN 100.000',         'detail' => '541233008821 • 03 Okt 19:03', 'amount' => 102500, 'status' => 'Proses'],
                ['icon' => 'sports_esports', 'title' => 'Diamond Mobile Legends 86', 'detail' => 'ID 4412876 • 03 Okt 14:27',   'amount' => 24300,  'status' => 'Sukses'],
            ],
        ]);
    }
}
