<?php

namespace App\Models;

use Database\Factories\MasterBankFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'bank_name', 'account_number', 'account_holder', 'is_active', 'sort_order'])]
class MasterBank extends Model
{
    /** @use HasFactory<MasterBankFactory> */
    use HasFactory;

    public const TYPE_BANK = 'bank';

    public const TYPE_EWALLET = 'ewallet';

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
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_BANK => 'Bank',
            self::TYPE_EWALLET => 'E-Wallet',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? ucfirst((string) $this->type);
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
