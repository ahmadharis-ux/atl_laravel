<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotDigiflazzProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'config_id',
        'digiflazz_product_id',
        'product_code',
        'product_name',
        'category',
        'brand',
        'type',
        'seller',
        'seller_sku_code',
        'seller_connection_type',
        'price',
        'stock',
        'unlimited_stock',
        'status',
        'start_cut_off',
        'end_cut_off',
        'description',
        'faktur',
        'multi',
        'multi_counter',
        'seller_sku_id',
        'seller_sku_id_int',
        'seller_details',
        'last_update',
        'is_seller_favorite',
        'rating',
        'rating_qty',
        'raw_data',
    ];

    protected $casts = [
        'unlimited_stock' => 'boolean',
        'status' => 'boolean',
        'seller_details' => 'array',
        'raw_data' => 'array',
    ];

    public function config()
    {
        return $this->belongsTo(BotDigiflazzConfig::class, 'config_id');
    }

    public function sellers()
    {
        return $this->hasMany(BotDigiflazzSeller::class, 'product_id');
    }
}
