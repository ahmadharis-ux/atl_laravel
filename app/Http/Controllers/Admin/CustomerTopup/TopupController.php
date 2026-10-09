<?php

namespace App\Http\Controllers\Admin\CustomerTopup;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TopupController extends Controller
{
    /**
     * Daftar transaksi topup (data hardcode sementara).
     */
    public function index(): View
    {
        $topups = [
            ['date' => '07 Okt 2026 09:12', 'trx' => 'TRX-20261007-0001', 'ref' => 'PAY-88910231', 'product' => 'Mobile Legends 86 Diamonds', 'game_id' => '183920144', 'amount' => 21500, 'status' => 'Success', 'sn' => 'SN-7712-9901-AAAA', 'provider' => 'DiamondStore'],
            ['date' => '07 Okt 2026 09:20', 'trx' => 'TRX-20261007-0002', 'ref' => 'PAY-88910242', 'product' => 'Free Fire 140 Diamonds', 'game_id' => '556120988', 'amount' => 19500, 'status' => 'Success', 'sn' => 'SN-7712-9902-BBBB', 'provider' => 'GarenaAPI'],
            ['date' => '07 Okt 2026 09:31', 'trx' => 'TRX-20261007-0003', 'ref' => 'PAY-88910255', 'product' => 'PUBG Mobile 60 UC', 'game_id' => '778901233', 'amount' => 15000, 'status' => 'Pending', 'sn' => '—', 'provider' => 'UniPin'],
            ['date' => '07 Okt 2026 09:45', 'trx' => 'TRX-20261007-0004', 'ref' => 'PAY-88910266', 'product' => 'Genshin Impact 300 Genesis', 'game_id' => '900112744', 'amount' => 75000, 'status' => 'Success', 'sn' => 'SN-7712-9904-CCCC', 'provider' => 'Moogold'],
            ['date' => '07 Okt 2026 10:02', 'trx' => 'TRX-20261007-0005', 'ref' => 'PAY-88910277', 'product' => 'Valorant 420 Points', 'game_id' => 'Atlantic#8891', 'amount' => 45000, 'status' => 'Failed', 'sn' => '—', 'provider' => 'SmileOne'],
            ['date' => '07 Okt 2026 10:15', 'trx' => 'TRX-20261007-0006', 'ref' => 'PAY-88910288', 'product' => 'Mobile Legends 172 Diamonds', 'game_id' => '183920155', 'amount' => 43500, 'status' => 'Processing', 'sn' => '—', 'provider' => 'DiamondStore'],
            ['date' => '07 Okt 2026 10:28', 'trx' => 'TRX-20261007-0007', 'ref' => 'PAY-88910299', 'product' => 'Free Fire 70 Diamonds', 'game_id' => '556120977', 'amount' => 10000, 'status' => 'Success', 'sn' => 'SN-7712-9907-DDDD', 'provider' => 'GarenaAPI'],
            ['date' => '07 Okt 2026 10:41', 'trx' => 'TRX-20261007-0008', 'ref' => 'PAY-88910310', 'product' => 'Honkai Star Rail 60 Oneiric', 'game_id' => '700221144', 'amount' => 21000, 'status' => 'Expired', 'sn' => '—', 'provider' => 'Moogold'],
            ['date' => '07 Okt 2026 11:03', 'trx' => 'TRX-20261007-0009', 'ref' => 'PAY-88910321', 'product' => 'PUBG Mobile 325 UC', 'game_id' => '778901244', 'amount' => 72000, 'status' => 'Success', 'sn' => 'SN-7712-9909-EEEE', 'provider' => 'UniPin'],
            ['date' => '07 Okt 2026 11:24', 'trx' => 'TRX-20261007-0010', 'ref' => 'PAY-88910332', 'product' => 'Clash of Clans 500 Gems', 'game_id' => 'Atlantic#7712', 'amount' => 65000, 'status' => 'Pending', 'sn' => '—', 'provider' => 'Codashop'],
        ];

        return view('admin.customer-topup.topup', [
            'topups' => $topups,
            'statusTones' => [
                'Success' => 'bg-mint-l tx-mintt',
                'Pending' => 'bg-gold tx-gold',
                'Processing' => 'bg-sky-l tx-navy',
                'Failed' => 'bg-errc-l tx-err',
                'Expired' => 'bg-tealc-m tx-tealt',
            ],
        ]);
    }
}
