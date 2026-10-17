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

    public function test_login_page_does_not_show_demo_accounts(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Akun demo')
            ->assertDontSee('user@atlantic.test')
            ->assertDontSee('admin@atlantic.test');
    }
}
