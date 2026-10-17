<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_the_users_real_balance_and_points(): void
    {
        $user = User::factory()->create([
            'balance' => 75_000,
            'points' => 3,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('balance', 75_000)
            ->assertViewHas('points', 3);
    }
}
