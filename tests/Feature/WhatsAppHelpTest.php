<?php

namespace Tests\Feature;

use App\Enums\DepositStatus;
use App\Enums\OrderStatus;
use App\Models\Deposit;
use App\Models\LandingSetting;
use App\Models\Order;
use App\Models\User;
use App\Support\SupportWhatsapp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppHelpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['support.whatsapp_number' => '']);
    }

    public function test_number_is_normalized_from_local_format(): void
    {
        config(['support.whatsapp_number' => '0812-3456-7890']);

        $this->assertSame('6281234567890', SupportWhatsapp::number());
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', (string) SupportWhatsapp::generalUrl());
    }

    public function test_number_falls_back_to_landing_setting(): void
    {
        LandingSetting::query()->create(array_merge(LandingSetting::defaults(), ['whatsapp_number' => '81234567890']));

        $this->assertSame('6281234567890', SupportWhatsapp::number());
    }

    public function test_returns_null_without_a_configured_number(): void
    {
        LandingSetting::query()->create(array_merge(LandingSetting::defaults(), ['whatsapp_number' => null]));

        $this->assertNull(SupportWhatsapp::number());
        $this->assertNull(SupportWhatsapp::generalUrl());
    }

    public function test_failed_order_shows_prefilled_whatsapp_button(): void
    {
        config(['support.whatsapp_number' => '08123456789']);

        $order = Order::factory()->create(['status' => OrderStatus::Failed]);

        $this->actingAs($order->user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('wa.me/628123456789', false)
            ->assertSee($order->order_number);
    }

    public function test_successful_order_does_not_show_whatsapp_button(): void
    {
        config(['support.whatsapp_number' => '08123456789']);

        $order = Order::factory()->create(['status' => OrderStatus::Success]);

        $this->actingAs($order->user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('wa.me/628123456789', false);
    }

    public function test_failed_deposit_shows_prefilled_whatsapp_button(): void
    {
        config(['support.whatsapp_number' => '08123456789']);

        $deposit = Deposit::factory()->create([
            'status' => DepositStatus::Failed,
            'user_id' => User::factory(),
        ]);

        $this->actingAs($deposit->user)
            ->get(route('deposits.show', $deposit))
            ->assertOk()
            ->assertSee('wa.me/628123456789', false)
            ->assertSee($deposit->reference);
    }

    public function test_whatsapp_button_is_hidden_when_number_is_not_configured(): void
    {
        LandingSetting::query()->create(array_merge(LandingSetting::defaults(), ['whatsapp_number' => null]));

        $order = Order::factory()->create(['status' => OrderStatus::Failed]);

        $this->actingAs($order->user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('wa.me/', false);
    }
}
