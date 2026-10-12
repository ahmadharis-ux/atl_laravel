<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_login_page_returns_a_successful_response(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_login_page_shows_demo_accounts(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Akun demo')
            ->assertSee('user@atlantic.test')
            ->assertSee('admin@atlantic.test');
    }
}
