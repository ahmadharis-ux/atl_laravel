<?php

return [
    'csp_report_only' => (bool) env('SECURITY_CSP_REPORT_ONLY', true),

    'csp_enforce' => (bool) env('SECURITY_CSP_ENFORCE', env('APP_ENV') === 'production'),

    'content_security_policy' => implode('; ', [
        "default-src 'self'",
        "base-uri 'self'",
        "frame-ancestors 'none'",
        "object-src 'none'",
        "img-src 'self' data: blob: https:",
        "font-src 'self' data: https://fonts.gstatic.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://www.gstatic.com https://www.google.com",
        "connect-src 'self' https:",
        "frame-src 'self' https://*.midtrans.com",
        "form-action 'self' https://*.midtrans.com",
    ]),
];
