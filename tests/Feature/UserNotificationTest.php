<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Order;
use App\Models\User;
use App\Notifications\TransactionResult;
use App\Support\DepositSettler;
use App\Support\OrderNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class UserNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_settling_a_deposit_notifies_the_owner(): void
    {
        $user = User::factory()->create();
        $deposit = Deposit::factory()->create(['user_id' => $user->getKey(), 'amount' => 50_000]);

        DepositSettler::settle($deposit);

        $notification = $user->notifications()->sole();
        $this->assertSame('Top up berhasil', $notification->data['title']);
        $this->assertSame('success', $notification->data['level']);
        $this->assertNull($notification->read_at);
    }

    public function test_order_notifier_creates_success_and_failure_notifications(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->getKey()]);

        OrderNotifier::success($order);
        OrderNotifier::failed($order, 'Gagal di provider.');

        $this->assertSame(2, $user->notifications()->count());
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->getKey(), 'type' => TransactionResult::class]);

        $titles = $user->notifications()->get()->map(fn ($n) => $n->data['title'])->all();
        $this->assertContains('Transaksi berhasil', $titles);
        $this->assertContains('Transaksi gagal', $titles);
    }

    public function test_notification_dropdown_returns_json_with_unread_count(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransactionResult('Halo', 'Pesan notifikasi.'));

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('latest_title', 'Halo')
            ->assertJsonPath('latest_level', 'info')
            ->assertJsonPath('latest_unread', true);
        $this->assertStringContainsString('Halo', $response->json('html'));
        $this->assertNotNull($response->json('latest_id'));
    }

    public function test_opening_a_notification_marks_it_read_and_redirects(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransactionResult('Halo', 'Pesan.', 'info', null, route('dashboard')));

        $notification = $user->notifications()->sole();

        $this->actingAs($user)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_opening_a_notification_redirects_to_same_host_path(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransactionResult('Halo', 'Pesan.', 'info', null, 'http://localhost:8000/transaksi/60'));

        $notification = $user->notifications()->sole();

        $this->actingAs($user)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect('/transaksi/60');
    }

    public function test_read_notifications_are_hidden_from_the_list(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransactionResult('Masih ada', 'a'));
        $user->notify(new TransactionResult('Sudah dibaca', 'b'));

        $read = $user->notifications()->get()->firstWhere('data.title', 'Sudah dibaca');
        $read->markAsRead();

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk()->assertJsonPath('unread', 1);
        $this->assertStringContainsString('Masih ada', $response->json('html'));
        $this->assertStringNotContainsString('Sudah dibaca', $response->json('html'));
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransactionResult('A', 'a'));
        $user->notify(new TransactionResult('B', 'b'));

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->notify(new TransactionResult('Rahasia', 'Pesan.'));
        $notification = $owner->notifications()->sole();

        $this->actingAs($other)
            ->get(route('notifications.open', $notification->id))
            ->assertNotFound();

        $this->assertSame(1, DatabaseNotification::query()->whereNull('read_at')->count());
    }
}
