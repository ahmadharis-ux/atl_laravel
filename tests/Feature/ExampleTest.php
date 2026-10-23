<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_landing_page(): void
    {
        $this->get('/')->assertOk()->assertSee('Atlantic Pedia');
    }

    public function test_login_page_returns_a_successful_response(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_login_page_does_not_show_demo_accounts(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Akun demo')
            ->assertDontSee('user@atlantic.test')
            ->assertDontSee('admin@atlantic.test');
    }
}
