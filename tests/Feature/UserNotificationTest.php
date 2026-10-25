<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Order;
use App\Models\Product;
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

    public function test_warning_notifications_use_a_warning_icon_by_default(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransactionResult('Bukti perlu diperbaiki', 'Mohon unggah ulang.', 'warning'));

        $notification = $user->notifications()->sole();

        $this->assertSame('warning', $notification->data['level']);
        $this->assertSame('warning', $notification->data['icon']);
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

    public function test_notification_page_shows_all_read_and_unread_with_tabs(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransactionResult('Ada yang baru', 'MARKER-UNREAD.'));
        $user->notify(new TransactionResult('Sudah selesai', 'MARKER-READ.'));

        $read = $user->notifications()->get()->firstWhere('data.title', 'Sudah selesai');
        $read->markAsRead();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('MARKER-UNREAD')
            ->assertSee('MARKER-READ')
            ->assertSee('seg-tabs', false);
    }

    public function test_notification_page_can_filter_read_and_unread(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransactionResult('Pesan belum', 'MARKER-UNREAD.'));
        $user->notify(new TransactionResult('Pesan sudah', 'MARKER-READ.'));

        $read = $user->notifications()->get()->firstWhere('data.title', 'Pesan sudah');
        $read->markAsRead();

        $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'unread']))
            ->assertOk()
            ->assertSee('MARKER-UNREAD')
            ->assertDontSee('MARKER-READ');

        $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'read']))
            ->assertOk()
            ->assertSee('MARKER-READ')
            ->assertDontSee('MARKER-UNREAD');
    }

    public function test_notification_page_can_search_by_keyword(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransactionResult('Top up berhasil', 'TRX-001 sukses.'));
        $user->notify(new TransactionResult('Transaksi gagal', 'TRX-999 gagal.'));

        $this->actingAs($user)
            ->get(route('notifications.index', ['q' => 'TRX-999']))
            ->assertOk()
            ->assertSee('TRX-999')
            ->assertDontSee('TRX-001');
    }

    public function test_order_notification_offers_beli_lagi_for_purchasable_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
            'target' => '081234567890',
        ]);

        OrderNotifier::success($order);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Beli Lagi')
            ->assertSee(route('checkout.show', $product).'?target=081234567890', false);
    }

    public function test_order_notification_offers_beli_lagi_in_dropdown_json(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
            'target' => '081234567890',
        ]);

        OrderNotifier::failed($order, 'Gagal di provider.');

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk();
        $this->assertStringContainsString('Beli Lagi', $response->json('html'));
    }

    public function test_order_notification_hides_beli_lagi_when_product_inactive(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => false]);
        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
            'target' => '081234567890',
        ]);

        OrderNotifier::success($order);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('Beli Lagi');
    }

    public function test_non_order_notification_has_no_beli_lagi(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransactionResult('Top up berhasil', 'Saldo bertambah.'));

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('Beli Lagi');
    }

    public function test_notification_page_shows_only_ten_items_with_load_more(): void
    {
        $user = User::factory()->create();
        for ($i = 1; $i <= 12; $i++) {
            $user->notify(new TransactionResult("Pesan $i", "BODY-$i."));
        }

        $response = $this->actingAs($user)->get(route('notifications.index'))->assertOk();

        $this->assertSame(10, substr_count($response->getContent(), 'notif-row-link'));
        $response->assertSee('Muat lebih banyak')
            ->assertSee('data-page="2"', false)
            ->assertSee(route('notifications.list'), false);
    }

    public function test_notification_list_endpoint_returns_rows_and_footer(): void
    {
        $user = User::factory()->create();
        for ($i = 1; $i <= 12; $i++) {
            $user->notify(new TransactionResult("Pesan $i", "BODY-$i."));
        }

        $page2 = $this->actingAs($user)->getJson(route('notifications.list', ['page' => 2]));

        $page2->assertOk();
        // 12 item, 10 per halaman -> halaman 2 menyisakan 2 baris.
        $this->assertSame(2, substr_count($page2->json('rows'), 'notif-row-link'));
        // Halaman terakhir tidak menyisakan tombol.
        $this->assertStringNotContainsString('data-ajax-more', $page2->json('footer'));
    }

    public function test_notification_list_endpoint_respects_filter(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransactionResult('Baru', 'ALPHAONLY.'));
        $user->notify(new TransactionResult('Lama', 'BETAONLY.'));

        $user->notifications()->get()->firstWhere('data.title', 'Lama')->markAsRead();

        $response = $this->actingAs($user)->getJson(route('notifications.list', ['filter' => 'unread']));

        $response->assertOk();
        $this->assertStringContainsString('ALPHAONLY.', $response->json('rows'));
        $this->assertStringNotContainsString('BETAONLY.', $response->json('rows'));
    }
}
