<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_security_headers_are_added_to_responses(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy-Report-Only');
    }

    public function test_hsts_is_added_to_secure_responses(): void
    {
        $this->get('https://localhost/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_staging_responses_can_be_marked_noindex(): void
    {
        config(['app.staging_noindex' => true]);

        $this->get('/login')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_csp_can_be_enforced(): void
    {
        config([
            'security.csp_enforce' => true,
            'security.csp_report_only' => true,
        ]);

        $this->get('/login')
            ->assertHeader('Content-Security-Policy')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    public function test_http_requests_are_redirected_when_force_https_is_enabled(): void
    {
        config(['app.force_https' => true]);

        $this->get('/login')
            ->assertRedirect('https://localhost:8000/login');
    }

    public function test_staging_basic_auth_can_protect_responses(): void
    {
        config([
            'app.staging_basic_auth' => true,
            'app.staging_basic_auth_username' => 'reviewer',
            'app.staging_basic_auth_password' => 'secret-password',
        ]);

        $this->get('/login')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Basic realm="Atlantic Pedia Staging"');

        $this->withBasicAuth('reviewer', 'secret-password')
            ->get('/login')
            ->assertOk();
    }
}
