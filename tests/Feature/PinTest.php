<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\PinAuditLog;
use App\Models\PinResetOtp;
use App\Models\PinResetToken;
use App\Models\Product;
use App\Models\User;
use App\Notifications\PinChangedNotification;
use App\Notifications\PinResetOtpNotification;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PinTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(array $attributes = []): Product
    {
        $category = Category::factory()->create(['slug' => 'games']);

        return Product::factory()->create(array_merge([
            'status' => true,
            'category_id' => $category->getKey(),
            'cost_price' => 10_000,
            'markup_value' => 0,
            'unlimited_stock' => true,
        ], $attributes));
    }

    public function test_verified_user_without_pin_is_forced_to_set_a_strong_pin(): void
    {
        $user = User::factory()->withoutPin()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('settings.pin'));

        $this->actingAs($user)
            ->get(route('settings.pin'))
            ->assertOk()
            ->assertSee('Atur PIN Transaksi');

        $this->actingAs($user)
            ->post(route('settings.pin.store'), [
                'pin' => '111111',
                'pin_confirmation' => '111111',
            ])
            ->assertSessionHasErrors('pin');

        $this->actingAs($user)
            ->post(route('settings.pin.store'), [
                'pin' => '905173',
                'pin_confirmation' => '905173',
            ])
            ->assertRedirect(route('settings.pin'))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertTrue($user->hasPin());
        $this->assertTrue(Hash::check('905173', $user->pin_hash));
        $this->assertDatabaseHas('pin_audit_logs', [
            'user_id' => $user->getKey(),
            'action' => 'pin.set',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_user_without_pin_cannot_start_or_complete_the_pin_reset_flow(): void
    {
        $user = User::factory()->withoutPin()->create();

        $this->actingAs($user)
            ->post(route('settings.pin.change.send'))
            ->assertRedirect(route('settings.pin'));

        $this->actingAs($user)
            ->get(route('settings.pin.change.verify'))
            ->assertRedirect(route('settings.pin'));

        $this->actingAs($user)
            ->post(route('settings.pin.change.verify.store'), ['otp' => '654321'])
            ->assertRedirect(route('settings.pin'));

        $this->actingAs($user)
            ->withSession(['pin_reset_token' => 'unused-token'])
            ->post(route('settings.pin.change.reset.store'), [
                'pin' => '905173',
                'pin_confirmation' => '905173',
            ])
            ->assertRedirect(route('settings.pin'));

        $this->assertFalse($user->fresh()->hasPin());
        $this->assertDatabaseCount('pin_reset_otps', 0);
        $this->assertDatabaseCount('pin_reset_tokens', 0);
    }

    public function test_saldo_checkout_requires_pin_and_tracks_failed_attempts(): void
    {
        $user = User::factory()->create(['balance' => 100_000]);
        $product = $this->product();
        $payload = [
            'target' => '123456789',
            'payment_method' => PaymentMethod::Saldo->value,
        ];

        $this->actingAs($user)
            ->post(route('checkout.store', $product), $payload)
            ->assertSessionHasErrors('pin');

        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($user)
            ->post(route('checkout.store', $product), $payload + ['pin' => '111111'])
            ->assertSessionHasErrors('pin');

        $this->assertSame(1, $user->fresh()->pin_attempts);
        $this->assertDatabaseHas('pin_audit_logs', [
            'user_id' => $user->getKey(),
            'action' => 'pin.verify_failed',
        ]);

        $this->actingAs($user)
            ->post(route('checkout.store', $product), $payload + ['pin' => UserFactory::DEFAULT_PIN])
            ->assertRedirect();

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(0, $user->fresh()->pin_attempts);
        $this->assertSame(90_000, $user->fresh()->balance);
        $this->assertDatabaseHas('pin_audit_logs', [
            'user_id' => $user->getKey(),
            'action' => 'pin.verified',
        ]);
    }

    public function test_pin_locks_after_five_failed_attempts_and_accepts_the_pin_after_lockout_expires(): void
    {
        $user = User::factory()->create([
            'balance' => 100_000,
            'pin_attempts' => 4,
        ]);
        $product = $this->product();
        $payload = [
            'target' => '123456789',
            'payment_method' => PaymentMethod::Saldo->value,
        ];

        $this->actingAs($user)
            ->post(route('checkout.store', $product), $payload + ['pin' => '111111'])
            ->assertSessionHasErrors('pin');

        $user->refresh();

        $this->assertSame(5, $user->pin_attempts);
        $this->assertNotNull($user->pin_locked_until);
        $this->assertTrue($user->pin_locked_until->isFuture());
        $this->assertDatabaseHas('pin_audit_logs', [
            'user_id' => $user->getKey(),
            'action' => 'pin.locked',
        ]);

        $this->actingAs($user)
            ->post(route('checkout.store', $product), $payload + ['pin' => UserFactory::DEFAULT_PIN])
            ->assertSessionHasErrors('pin');

        $this->assertDatabaseCount('orders', 0);

        $this->travel(16)->minutes();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), $payload + ['pin' => UserFactory::DEFAULT_PIN])
            ->assertRedirect();

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(0, $user->fresh()->pin_attempts);
        $this->assertNull($user->fresh()->pin_locked_until);
    }

    public function test_high_value_checkout_is_blocked_after_pin_reset_until_the_window_passes(): void
    {
        $user = User::factory()->create([
            'balance' => 1_000_000,
            'pin_reset_at' => now()->subMinute(),
        ]);
        $product = $this->product(['cost_price' => 600_000, 'markup_value' => 0]);
        $payload = [
            'target' => '123456789',
            'payment_method' => PaymentMethod::Saldo->value,
            'pin' => UserFactory::DEFAULT_PIN,
        ];

        $this->actingAs($user)
            ->post(route('checkout.store', $product), $payload)
            ->assertSessionHasErrors('pin');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1_000_000, $user->fresh()->balance);

        $this->travel(25)->hours();

        $this->actingAs($user)
            ->post(route('checkout.store', $product), $payload)
            ->assertRedirect();

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(400_000, $user->fresh()->balance);
    }

    public function test_otp_reset_changes_pin_consumes_tokens_and_sends_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $otp = PinResetOtp::query()->create([
            'user_id' => $user->getKey(),
            'code_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($user)
            ->post(route('settings.pin.change.verify.store'), ['otp' => '654321'])
            ->assertRedirect(route('settings.pin.change.reset'))
            ->assertSessionHas('pin_reset_token');

        $this->actingAs($user)
            ->get(route('settings.pin.change.reset'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('settings.pin.change.reset.store'), [
                'pin' => '905173',
                'pin_confirmation' => '905173',
            ])
            ->assertRedirect(route('settings.pin'))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertTrue(Hash::check('905173', $user->pin_hash));
        $this->assertNotNull($user->pin_reset_at);
        $this->assertNotNull($otp->fresh()->consumed_at);
        $this->assertNotNull(PinResetToken::query()->sole()->consumed_at);
        $this->assertDatabaseHas('pin_audit_logs', [
            'user_id' => $user->getKey(),
            'action' => 'pin.reset_otp_verified',
        ]);
        $this->assertDatabaseHas('pin_audit_logs', [
            'user_id' => $user->getKey(),
            'action' => 'pin.reset',
        ]);

        Notification::assertSentTo($user, PinChangedNotification::class);

        $audit = PinAuditLog::query()->get()->toJson();
        $this->assertStringNotContainsString('654321', $audit);
        $this->assertStringNotContainsString('905173', $audit);
    }

    public function test_otp_requests_are_rate_limited(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.pin.change.send'))
            ->assertRedirect(route('settings.pin.change.verify'));

        Notification::assertSentTo($user, PinResetOtpNotification::class);

        $this->actingAs($user)
            ->post(route('settings.pin.change.send'))
            ->assertSessionHasErrors('otp');

        $this->assertDatabaseCount('pin_reset_otps', 1);
    }

    public function test_five_invalid_otp_attempts_consume_the_otp(): void
    {
        $user = User::factory()->create();
        $otp = PinResetOtp::query()->create([
            'user_id' => $user->getKey(),
            'code_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(10),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($user)
                ->post(route('settings.pin.change.verify.store'), ['otp' => '111111'])
                ->assertSessionHasErrors('otp');
        }

        $this->assertSame(5, $otp->fresh()->attempts);
        $this->assertNotNull($otp->fresh()->consumed_at);

        $this->actingAs($user)
            ->post(route('settings.pin.change.verify.store'), ['otp' => '654321'])
            ->assertSessionHasErrors('otp');

        $this->assertDatabaseCount('pin_reset_tokens', 0);
    }

    public function test_prune_command_removes_expired_and_consumed_pin_reset_records(): void
    {
        $user = User::factory()->create();
        $expiredOtp = PinResetOtp::query()->create([
            'user_id' => $user->getKey(),
            'code_hash' => Hash::make('654321'),
            'expires_at' => now()->subMinute(),
        ]);
        $activeOtp = PinResetOtp::query()->create([
            'user_id' => $user->getKey(),
            'code_hash' => Hash::make('905173'),
            'expires_at' => now()->addMinutes(10),
        ]);
        $expiredToken = PinResetToken::query()->create([
            'user_id' => $user->getKey(),
            'pin_reset_otp_id' => $expiredOtp->getKey(),
            'token_hash' => hash('sha256', 'expired-token'),
            'expires_at' => now()->subMinute(),
        ]);
        $activeToken = PinResetToken::query()->create([
            'user_id' => $user->getKey(),
            'pin_reset_otp_id' => $activeOtp->getKey(),
            'token_hash' => hash('sha256', 'active-token'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->artisan('pin:prune')->assertSuccessful();

        $this->assertDatabaseMissing('pin_reset_otps', ['id' => $expiredOtp->getKey()]);
        $this->assertDatabaseHas('pin_reset_otps', ['id' => $activeOtp->getKey()]);
        $this->assertDatabaseMissing('pin_reset_tokens', ['id' => $expiredToken->getKey()]);
        $this->assertDatabaseHas('pin_reset_tokens', ['id' => $activeToken->getKey()]);
    }
}
