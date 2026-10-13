<?php

namespace App\Models;

use Database\Factories\MasterBankFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['bank_name', 'account_number', 'account_holder', 'is_active', 'sort_order'])]
class MasterBank extends Model
{
    /** @use HasFactory<MasterBankFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Daftar nama bank & e-wallet untuk pilihan (value => label).
     *
     * @return array<string, string>
     */
    public static function nameOptions(): array
    {
        $names = [
            // Bank
            'BCA', 'BNI', 'BRI', 'Bank Mandiri', 'BSI', 'CIMB Niaga', 'Permata',
            'Danamon', 'BTN', 'OCBC', 'Panin', 'Maybank', 'Bank Mega', 'Bukopin',
            'Bank Muamalat', 'BTPN', 'Bank Jago', 'Bank Neo Commerce', 'SeaBank',
            'Allo Bank', 'Bank DKI', 'Bank Jateng', 'Bank Jatim', 'Bank Sumut',
            'Bank Kaltimtara',
            // E-Wallet
            'GoPay', 'OVO', 'DANA', 'ShopeePay', 'LinkAja', 'Jenius', 'Sakuku',
            'i.saku', 'MotionPay', 'DOKU', 'Flip',
        ];

        return array_combine($names, $names);
    }
}
