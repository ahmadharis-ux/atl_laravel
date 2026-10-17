<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_register_a_device_token(): void
    {
        $this->post(route('notifications.token.store'), ['token' => 'abc'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('device_tokens', 0);
    }

    public function test_user_can_register_a_device_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('notifications.token.store'), ['token' => 'device-token-123'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->getKey(),
            'token' => 'device-token-123',
            'platform' => 'web',
        ]);
    }

    public function test_registering_the_same_token_twice_does_not_duplicate(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('notifications.token.store'), ['token' => 'same']);
        $this->actingAs($user)->postJson(route('notifications.token.store'), ['token' => 'same']);

        $this->assertDatabaseCount('device_tokens', 1);
    }

    public function test_token_owned_by_another_user_is_transferred(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        DeviceToken::factory()->for($first)->create(['token' => 'shared-device']);

        $this->actingAs($second)
            ->postJson(route('notifications.token.store'), ['token' => 'shared-device'])
            ->assertOk();

        $this->assertDatabaseCount('device_tokens', 1);
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $second->getKey(),
            'token' => 'shared-device',
        ]);
    }

    public function test_user_can_remove_own_token(): void
    {
        $user = User::factory()->create();
        DeviceToken::factory()->for($user)->create(['token' => 'mine']);

        $this->actingAs($user)
            ->deleteJson(route('notifications.token.destroy'), ['token' => 'mine'])
            ->assertOk();

        $this->assertDatabaseCount('device_tokens', 0);
    }

    public function test_user_cannot_remove_another_users_token(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        DeviceToken::factory()->for($owner)->create(['token' => 'owner-token']);

        $this->actingAs($other)
            ->deleteJson(route('notifications.token.destroy'), ['token' => 'owner-token'])
            ->assertOk();

        $this->assertDatabaseHas('device_tokens', ['token' => 'owner-token']);
    }
}
