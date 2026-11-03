<?php

return [
    'coming_soon_until' => env('LANDING_COMING_SOON_UNTIL', '2000-01-01 00:00:00'),
    'coming_soon_allowed_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('LANDING_COMING_SOON_ALLOWED_IPS', '')),
    ))),
];
