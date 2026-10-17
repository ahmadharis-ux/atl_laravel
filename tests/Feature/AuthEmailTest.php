<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_email_template_is_themed_and_contains_the_link(): void
    {
        $html = view('emails.verify-email', [
            'name' => 'Budi',
            'url' => 'https://atlanticpedia.test/email/verify/1/abcdef',
        ])->render();

        $this->assertStringContainsString('Atlantic Pedia', $html);
        $this->assertStringContainsString('Verifikasi email kamu', $html);
        $this->assertStringContainsString('Budi', $html);
        $this->assertStringContainsString('https://atlanticpedia.test/email/verify/1/abcdef', $html);
    }

    public function test_reset_password_email_template_is_themed_and_contains_the_link(): void
    {
        $html = view('emails.reset-password', [
            'name' => 'Budi',
            'url' => 'https://atlanticpedia.test/reset-password/token123',
            'count' => 60,
        ])->render();

        $this->assertStringContainsString('Atlantic Pedia', $html);
        $this->assertStringContainsString('Atur ulang kata sandi', $html);
        $this->assertStringContainsString('Budi', $html);
        $this->assertStringContainsString('https://atlanticpedia.test/reset-password/token123', $html);
        $this->assertStringContainsString('60 menit', $html);
    }

    public function test_verification_notification_uses_the_custom_mail_view(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmailNotification::class, function (VerifyEmailNotification $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            return $mail->view === 'emails.verify-email'
                && str_contains((string) $mail->viewData['url'], '/email/verify/')
                && str_contains(view($mail->view, $mail->viewData)->render(), 'Verifikasi email kamu');
        });
    }

    public function test_password_reset_notification_uses_the_custom_mail_view(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect();

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            return $mail->view === 'emails.reset-password'
                && str_contains((string) $mail->viewData['url'], '/reset-password/')
                && str_contains(view($mail->view, $mail->viewData)->render(), 'Atur ulang kata sandi');
        });
    }
}
