<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TwoFactorAuthentication;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('user', 'web');
    }

    public function test_registration_creates_an_unverified_user_and_redirects_to_the_notice(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'budi@example.com')->sole();

        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_user_is_redirected_to_the_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Verifikasi email Anda');
    }

    public function test_the_forgot_and_reset_password_pages_render(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Lupa kata sandi?');

        $this->get(route('password.reset', ['token' => 'dummy-token', 'email' => 'a@b.com']))
            ->assertOk()
            ->assertSee('dummy-token', false);
    }

    public function test_the_two_factor_challenge_page_requires_a_pending_login(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
    }

    public function test_user_can_verify_their_email_with_a_signed_link(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_the_verification_email_can_be_resent(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertSessionHas('status');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_a_password_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_a_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('sandi-baru-123', $user->fresh()->password));
    }

    public function test_login_without_two_factor_logs_the_user_in(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_two_factor_requires_the_challenge(): void
    {
        $secret = TwoFactorAuthentication::generateSecret();

        $user = User::factory()->create([
            'password' => 'password',
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();

        $this->post(route('two-factor.challenge.verify'), [
            'code' => TwoFactorAuthentication::currentCode($secret),
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_two_factor_challenge_rejects_an_invalid_code(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'two_factor_secret' => TwoFactorAuthentication::generateSecret(),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->post(route('two-factor.challenge.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_google_redirect_returns_to_login_when_not_configured(): void
    {
        config()->set('services.google.client_id', '');

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }
}
