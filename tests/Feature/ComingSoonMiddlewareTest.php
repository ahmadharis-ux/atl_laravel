<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComingSoonMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'landing.coming_soon_until' => now('Asia/Jakarta')->addHour()->format('Y-m-d H:i:s'),
            'landing.coming_soon_timezone' => 'Asia/Jakarta',
            'landing.coming_soon_allowed_ips' => [],
        ]);
    }

    public function test_all_web_pages_are_blocked_during_coming_soon_window(): void
    {
        $this->get(route('landing'))
            ->assertStatus(503)
            ->assertSee('Segera Hadir');

        $this->get(route('login'))
            ->assertStatus(503)
            ->assertSee('Segera Hadir');

        $this->get('/produk')
            ->assertStatus(503)
            ->assertSee('Segera Hadir');
    }

    public function test_web_pages_are_available_after_coming_soon_window(): void
    {
        config([
            'landing.coming_soon_until' => now('Asia/Jakarta')->subSecond()->format('Y-m-d H:i:s'),
        ]);

        $this->get(route('landing'))
            ->assertOk();
    }
}
