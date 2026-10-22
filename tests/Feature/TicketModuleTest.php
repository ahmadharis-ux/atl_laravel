<?php

namespace Tests\Feature;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TicketModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['dashboard.view', 'tickets.view', 'tickets.update']): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create(['is_admin' => true]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_user_can_create_complaint_ticket_and_admin_is_notified(): void
    {
        $user = User::factory()->create();
        $admin = $this->admin();

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'category' => TicketCategory::Complaint->value,
                'subject' => 'Transaksi tidak masuk',
                'description' => 'Sudah bayar tetapi produk belum diterima.',
            ])
            ->assertRedirect();

        $ticket = Ticket::query()->firstOrFail();

        $this->assertSame($user->getKey(), $ticket->user_id);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertStringStartsWith('TKT-', $ticket->code);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->getKey(),
            'notifiable_type' => $admin->getMorphClass(),
        ]);
    }

    public function test_supplier_ticket_requires_company_details(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'category' => TicketCategory::Supplier->value,
                'subject' => 'Kerja sama supplier',
                'description' => 'Kami ingin memasok produk digital.',
            ])
            ->assertSessionHasErrors(['company_name', 'company_phone']);

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_user_cannot_view_another_users_ticket(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ticket = Ticket::query()->create([
            'user_id' => $owner->getKey(),
            'code' => 'TKT-20260101-ABCDEF',
            'category' => TicketCategory::Complaint,
            'subject' => 'Subjek',
            'description' => 'Deskripsi kendala yang cukup panjang.',
        ]);

        $this->actingAs($other)->get(route('tickets.show', $ticket))->assertNotFound();
        $this->actingAs($owner)->get(route('tickets.show', $ticket))->assertOk();
    }

    public function test_admin_reply_moves_ticket_to_progress_and_notifies_user(): void
    {
        $user = User::factory()->create();
        $admin = $this->admin();
        $ticket = Ticket::query()->create([
            'user_id' => $user->getKey(),
            'code' => 'TKT-20260101-ABCDEF',
            'category' => TicketCategory::Complaint,
            'subject' => 'Subjek',
            'description' => 'Deskripsi kendala yang cukup panjang.',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.tickets.reply', $ticket), ['body' => 'Kami sedang memeriksa.'])
            ->assertRedirect();

        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);
        $this->assertDatabaseHas('ticket_replies', ['ticket_id' => $ticket->getKey(), 'is_staff' => true]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->getKey()]);
    }

    public function test_admin_can_close_ticket_and_user_cannot_reply_after_final_status(): void
    {
        $user = User::factory()->create();
        $admin = $this->admin();
        $ticket = Ticket::query()->create([
            'user_id' => $user->getKey(),
            'code' => 'TKT-20260101-ABCDEF',
            'category' => TicketCategory::Suggestion,
            'subject' => 'Saran',
            'description' => 'Masukan untuk aplikasi.',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.tickets.status', $ticket), ['status' => TicketStatus::Resolved->value])
            ->assertRedirect();

        $this->assertNotNull($ticket->fresh()->closed_at);

        $this->actingAs($user)
            ->post(route('tickets.reply', $ticket), ['body' => 'Terima kasih.'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_admin_without_ticket_permission_cannot_view_tickets(): void
    {
        $admin = $this->admin(['dashboard.view']);

        $this->actingAs($admin)->get(route('admin.tickets.index'))->assertForbidden();
    }
}
