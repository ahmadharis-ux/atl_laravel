<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use App\Notifications\TransactionResult;
use App\Support\FcmSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_worker_route_serves_javascript(): void
    {
        config([
            'firebase.client.api_key' => 'test-api-key',
            'firebase.client.project_id' => 'demo-project',
            'firebase.client.messaging_sender_id' => '123456',
            'firebase.client.app_id' => '1:123456:web:abc',
        ]);

        $response = $this->get(route('firebase.sw'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/javascript');

        $content = (string) $response->getContent();
        $this->assertStringContainsString('importScripts', $content);
        $this->assertStringContainsString('firebase.initializeApp', $content);
        $this->assertStringContainsString('test-api-key', $content);
    }

    public function test_settings_notifications_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.notifications'))
            ->assertOk()
            ->assertSee('Aktifkan Notifikasi', false)
            ->assertSee('data-push-toggle', false);
    }

    public function test_transaction_result_is_pushed_and_stale_tokens_are_pruned(): void
    {
        config(['firebase.credentials' => '{"type":"service_account","project_id":"demo-project"}', 'firebase.project_id' => 'demo-project']);

        $user = User::factory()->create();
        DeviceToken::factory()->for($user)->create(['token' => 'valid-token']);
        DeviceToken::factory()->for($user)->create(['token' => 'stale-token']);

        $report = MulticastSendReport::withItems([
            SendReport::success(
                MessageTarget::with(MessageTarget::TOKEN, 'valid-token'),
                ['name' => 'projects/demo-project/messages/1'],
            ),
            SendReport::failure(
                MessageTarget::with(MessageTarget::TOKEN, 'stale-token'),
                new NotFound('Requested entity was not found.'),
            ),
        ]);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('sendMulticast')->once()->andReturn($report);

        $this->app->instance(FcmSender::class, new FcmSender($messaging));

        $user->notify(new TransactionResult(
            title: 'Top up berhasil',
            body: 'Saldo bertambah Rp 50.000.',
            level: 'success',
        ));

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->getKey()]);
        $this->assertDatabaseHas('device_tokens', ['token' => 'valid-token']);
        $this->assertDatabaseMissing('device_tokens', ['token' => 'stale-token']);
    }

    public function test_push_is_skipped_when_firebase_is_not_configured(): void
    {
        config(['firebase.credentials' => null]);

        $user = User::factory()->create();
        DeviceToken::factory()->for($user)->create(['token' => 'kept-token']);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldNotReceive('sendMulticast');

        $this->app->instance(FcmSender::class, new FcmSender($messaging));

        $user->notify(new TransactionResult(title: 'Halo', body: 'Pesan.'));

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->getKey()]);
        $this->assertDatabaseHas('device_tokens', ['token' => 'kept-token']);
    }

    public function test_push_is_skipped_when_the_credentials_file_is_missing(): void
    {
        config(['firebase.credentials' => 'storage/app/firebase/does-not-exist.json']);

        $user = User::factory()->create();
        DeviceToken::factory()->for($user)->create(['token' => 'kept-token']);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldNotReceive('sendMulticast');

        $this->app->instance(FcmSender::class, new FcmSender($messaging));

        $user->notify(new TransactionResult(title: 'Halo', body: 'Pesan.'));

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->getKey()]);
        $this->assertDatabaseHas('device_tokens', ['token' => 'kept-token']);
    }
}
