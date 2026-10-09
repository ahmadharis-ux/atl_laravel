<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = now()->locale('id');

        return view('admin.dashboard', [
            'user' => ['name' => auth()->user()->name, 'role' => 'Administrator'],
            'dateLong' => $now->translatedFormat('l, d F Y'),
            'stats' => [
                ['label' => 'Total Pengguna', 'value' => User::count(), 'icon' => 'group', 'tone' => 'bg-tealc-m tx-tealt'],
                ['label' => 'Transaksi Hari Ini', 'value' => 128, 'icon' => 'receipt_long', 'tone' => 'bg-mint-l tx-mintt'],
                ['label' => 'Produk Aktif', 'value' => 42, 'icon' => 'inventory_2', 'tone' => 'bg-sky-l tx-navy'],
                ['label' => 'Pendapatan', 'value' => 'Rp 12,4 Jt', 'icon' => 'payments', 'tone' => 'bg-gold tx-gold'],
            ],
            'recentUsers' => User::latest()->take(5)->get(),
        ]);
    }
}
