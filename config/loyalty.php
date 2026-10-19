<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Threshold Tier Loyalty
    |--------------------------------------------------------------------------
    |
    | Poin reward bertambah otomatis 1 poin per order lunas dan 1 poin per
    | top up lunas. Tier ditentukan dari akumulasi poin seumur hidup:
    |
    |   Silver   : 0 poin ke atas (default semua user)
    |   Gold     : gold_points ke atas
    |   Platinum : platinum_points ke atas
    |
    */
    'gold_points' => (int) env('LOYALTY_GOLD_POINTS', 100),
    'platinum_points' => (int) env('LOYALTY_PLATINUM_POINTS', 500),

];
