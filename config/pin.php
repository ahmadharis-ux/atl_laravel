<?php

return [
    'max_attempts' => (int) env('PIN_MAX_ATTEMPTS', 5),
    'lockout_minutes' => (int) env('PIN_LOCKOUT_MINUTES', 15),
    'otp_ttl_minutes' => (int) env('PIN_OTP_TTL_MINUTES', 10),
    'otp_resend_seconds' => (int) env('PIN_OTP_RESEND_SECONDS', 60),
    'otp_max_per_hour' => (int) env('PIN_OTP_MAX_PER_HOUR', 5),
    'otp_max_attempts' => (int) env('PIN_OTP_MAX_ATTEMPTS', 5),
    'reset_token_ttl_minutes' => (int) env('PIN_RESET_TOKEN_TTL_MINUTES', 10),
    'high_value_threshold' => (int) env('PIN_HIGH_VALUE_THRESHOLD', 500_000),
    'high_value_window_hours' => (int) env('PIN_HIGH_VALUE_WINDOW_HOURS', 24),
];
