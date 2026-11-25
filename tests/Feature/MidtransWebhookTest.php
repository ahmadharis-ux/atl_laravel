<?php

namespace Tests\Feature;

use App\Enums\DepositStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function configureMidtrans(): void
    {
        config()->set('midtrans.server_key', 'SB-Mid-server-test');
        config()->set('midtrans.client_key', 'SB-Mid-client-test');
    }

    private function payload(Deposit $deposit, string $status = 'settlement'): array
    {
        $orderId = $deposit->reference;
        $statusCode = '200';
        $gross = (string) $deposit->amount;

        return [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $gross,
            'transaction_status' => $status,
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', $orderId.$statusCode.$gross.'SB-Mid-server-test'),
        ];
    }

    public function test_a_settlement_notification_credits_the_balance(): void
    {
        $this->configureMidtrans();

        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'amount' => 75_000,
            'payment_method' => PaymentMethod::Qris,
        ]);

        $this->postJson(route('webhook.midtrans'), $this->payload($deposit))
            ->assertOk();

        $deposit->refresh();
        $this->assertSame(DepositStatus::Paid, $deposit->status);
        $this->assertSame(75_000, $user->fresh()->balance);
    }

    public function test_a_notification_with_an_invalid_signature_is_ignored(): void
    {
        $this->configureMidtrans();

        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'amount' => 75_000,
            'payment_method' => PaymentMethod::Qris,
        ]);

        $payload = $this->payload($deposit);
        $payload['signature_key'] = 'invalid';

        $this->postJson(route('webhook.midtrans'), $payload)->assertOk();

        $this->assertSame(DepositStatus::Pending, $deposit->fresh()->status);
        $this->assertSame(0, $user->fresh()->balance);
    }

    public function test_a_notification_is_ignored_when_midtrans_is_not_configured(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $user->getKey(),
            'amount' => 75_000,
            'payment_method' => PaymentMethod::Qris,
        ]);

        $this->postJson(route('webhook.midtrans'), [
            'order_id' => $deposit->reference,
            'status_code' => '200',
            'gross_amount' => '75000',
            'transaction_status' => 'settlement',
        ])->assertOk();

        $this->assertSame(DepositStatus::Pending, $deposit->fresh()->status);
        $this->assertSame(0, $user->fresh()->balance);
    }

    public function test_an_expire_notification_marks_the_deposit_expired(): void
    {
        $this->configureMidtrans();

        $deposit = Deposit::factory()->create([
            'amount' => 75_000,
            'payment_method' => PaymentMethod::Qris,
        ]);

        $this->postJson(route('webhook.midtrans'), $this->payload($deposit, 'expire'))
            ->assertOk();

        $this->assertSame(DepositStatus::Expired, $deposit->fresh()->status);
    }

    public function test_qris_charge_is_skipped_when_midtrans_is_not_configured(): void
    {
        $deposit = Deposit::factory()->create([
            'amount' => 75_000,
            'payment_method' => PaymentMethod::Qris,
        ]);

        $service = app(MidtransService::class);

        $this->assertFalse($service->isConfigured());
        $this->assertNull($service->chargeQris($deposit));
    }

    public function test_qris_paid_after_the_order_expired_is_refunded_to_saldo_once(): void
    {
        $this->configureMidtrans();

        $user = User::factory()->create(['balance' => 0]);
        $order = Order::factory()->create([
            'user_id' => $user->getKey(),
            'status' => OrderStatus::Expired,
            'payment_method' => PaymentMethod::Qris,
            'amount' => 20_000,
            'fee' => 140,
            'paid_at' => null,
        ]);
        $gross = (string) $order->totalDue();
        $payload = [
            'order_id' => $order->order_number,
            'status_code' => '200',
            'gross_amount' => $gross,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'signature_key' => hash('sha512', $order->order_number.'200'.$gross.'SB-Mid-server-test'),
        ];

        $this->postJson(route('webhook.midtrans'), $payload)->assertOk();
        $this->postJson(route('webhook.midtrans'), $payload)->assertOk();

        $this->assertSame(20_140, $user->fresh()->balance);
        $this->assertSame(OrderStatus::Expired, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->refunded_at);
    }
}
