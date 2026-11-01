<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Loyalty Rewards
    |--------------------------------------------------------------------------
    |
    | Reward order dihitung dari persentase margin setelah biaya, lalu
    | dikonversi ke poin. Tier menggunakan total poin reward seumur hidup.
    |
    */
    'earn_percent' => 30,
    'rupiah_per_point' => 10,
    'gold_points' => (int) env('LOYALTY_GOLD_POINTS', 100),
    'platinum_points' => (int) env('LOYALTY_PLATINUM_POINTS', 500),

];
