<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotDigiflazzSeller extends Model
{
    use HasFactory;

    protected $fillable = [
        'config_id',
        'product_id',
        'seller_name',
        'seller_sku_code',
        'seller_connection_type',
        'price',
        'stock',
        'unlimited_stock',
        'status',
        'start_cut_off',
        'end_cut_off',
        'faktur',
        'multi',
        'multi_counter',
        'seller_sku_id',
        'seller_sku_id_int',
        'is_seller_favorite',
        'rating',
        'rating_qty',
        'deskripsi',
        'seller_details',
        'raw_data',
    ];

    protected $casts = [
        'unlimited_stock' => 'boolean',
        'status' => 'boolean',
        'is_seller_favorite' => 'boolean',
        'multi' => 'boolean',
        'seller_details' => 'array',
        'raw_data' => 'array',
    ];

    public function config()
    {
        return $this->belongsTo(BotDigiflazzConfig::class, 'config_id');
    }

    public function product()
    {
        return $this->belongsTo(BotDigiflazzProduct::class, 'product_id');
    }
}
