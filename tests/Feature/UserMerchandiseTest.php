<?php

namespace Tests\Feature;

use App\Enums\MerchandiseRedemptionStatus;
use App\Models\Merchandise;
use App\Models\User;
use App\Support\MerchandiseRedeemer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMerchandiseTest extends TestCase
{
    use RefreshDatabase;

    private function merchandise(array $attributes = []): Merchandise
    {
        return Merchandise::factory()->create(array_merge([
            'is_active' => true,
            'points_cost' => 100,
            'stock' => 10,
        ], $attributes));
    }

    public function test_user_can_view_the_merchandise_catalog(): void
    {
        $user = User::factory()->create(['points' => 200]);
        $this->merchandise();

        $this->actingAs($user)
            ->get(route('merchandise.index'))
            ->assertOk()
            ->assertSee('Merchandise');
    }

    public function test_inactive_items_are_hidden_from_the_catalog(): void
    {
        $user = User::factory()->create();
        $this->merchandise(['name' => 'Tumbler Emas', 'is_active' => false]);

        $this->actingAs($user)
            ->get(route('merchandise.index'))
            ->assertOk()
            ->assertDontSee('Tumbler Emas');
    }

    public function test_user_can_redeem_points_for_merchandise(): void
    {
        $user = User::factory()->create(['points' => 150]);
        $item = $this->merchandise(['points_cost' => 100, 'stock' => 3]);

        $this->actingAs($user)
            ->post(route('merchandise.redeem', $item))
            ->assertRedirect()
            ->assertSessionHas('status');

        $user->refresh();
        $item->refresh();

        $this->assertSame(50, (int) $user->points);
        $this->assertSame(2, $item->stock);

        $redemption = $user->merchandiseRedemptions()->first();
        $this->assertNotNull($redemption);
        $this->assertSame(MerchandiseRedemptionStatus::Pending, $redemption->status);
        $this->assertSame(100, (int) $redemption->points_cost);
    }

    public function test_redeem_fails_when_points_are_insufficient(): void
    {
        $user = User::factory()->create(['points' => 10]);
        $item = $this->merchandise(['points_cost' => 100]);

        $this->actingAs($user)
            ->post(route('merchandise.redeem', $item))
            ->assertSessionHasErrors('points');

        $this->assertSame(10, (int) $user->refresh()->points);
        $this->assertSame(10, $item->refresh()->stock);
        $this->assertSame(0, $user->merchandiseRedemptions()->count());
    }

    public function test_redeem_fails_when_stock_is_empty(): void
    {
        $user = User::factory()->create(['points' => 200]);
        $item = $this->merchandise(['points_cost' => 100, 'stock' => 0]);

        $this->actingAs($user)
            ->post(route('merchandise.redeem', $item))
            ->assertSessionHasErrors('merchandise');

        $this->assertSame(200, (int) $user->refresh()->points);
        $this->assertSame(0, $user->merchandiseRedemptions()->count());
    }

    public function test_redeem_fails_when_merchandise_is_inactive(): void
    {
        $user = User::factory()->create(['points' => 200]);
        $item = $this->merchandise(['points_cost' => 100, 'is_active' => false]);

        $this->actingAs($user)
            ->post(route('merchandise.redeem', $item))
            ->assertSessionHasErrors('merchandise');

        $this->assertSame(200, (int) $user->refresh()->points);
        $this->assertSame(10, $item->refresh()->stock);
    }

    public function test_cancelling_a_redemption_refunds_points_and_restocks(): void
    {
        $user = User::factory()->create(['points' => 100]);
        $item = $this->merchandise(['points_cost' => 80, 'stock' => 1]);

        $this->actingAs($user)
            ->post(route('merchandise.redeem', $item))
            ->assertRedirect();

        $redemption = $user->merchandiseRedemptions()->first();
        $this->assertNotNull($redemption);

        $this->assertTrue(MerchandiseRedeemer::cancel($redemption, 'stok tidak ada'));

        $this->assertSame(100, (int) $user->refresh()->points);
        $this->assertSame(1, $item->refresh()->stock);
        $this->assertSame(MerchandiseRedemptionStatus::Cancelled, $redemption->fresh()->status);
    }
}
