<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Support\ProductRegion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderProviderCustomerNoTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_customer_no_uses_dot_separator_with_zone(): void
    {
        $order = Order::factory()->create([
            'target' => '12345678',
            'target_zone' => '9728',
        ]);

        $this->assertSame('12345678.9728', $order->providerCustomerNo());
    }

    public function test_provider_customer_no_without_zone_returns_target(): void
    {
        $order = Order::factory()->create([
            'target' => '087800001230',
            'target_zone' => null,
        ]);

        $this->assertSame('087800001230', $order->providerCustomerNo());
    }

    public function test_product_region_parses_parenthetical_token(): void
    {
        $this->assertSame('Malaysia', ProductRegion::parse('Mobile Legends 112 Diamonds (102+10) (Malaysia)'));
        $this->assertSame('Global', ProductRegion::parse('Mobile Legends 86 Diamonds (Global)'));
        $this->assertNull(ProductRegion::parse('1 Big Cat Coins'));
    }

    public function test_product_region_label_defaults_to_umum(): void
    {
        $this->assertSame('Umum', ProductRegion::label(null));
        $this->assertSame('Malaysia', ProductRegion::label('Malaysia'));
    }
}
