<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NicknameLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_check_a_game_nickname(): void
    {
        Http::fake([
            'api.isan.eu.org/nickname/ml*' => Http::response([
                'success' => true,
                'name' => 'Budi',
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('products.nickname', ['brand' => 'mobile-legends', 'target' => '123', 'zone' => '4']))
            ->assertOk()
            ->assertJson(['status' => true, 'nickname' => 'Budi']);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.isan.eu.org/nickname/ml?id=123&server=4');
    }

    public function test_unknown_nickname_returns_a_message(): void
    {
        Http::fake([
            'api.isan.eu.org/nickname/ff*' => Http::response([
                'success' => true,
                'game' => 'Garena Free Fire',
                'id' => 999,
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('products.nickname', ['brand' => 'free-fire', 'target' => '999']))
            ->assertOk()
            ->assertJson(['status' => false, 'message' => 'Nama akun tidak ditemukan.']);
    }

    public function test_target_is_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('products.nickname', ['brand' => 'free-fire']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('target');
    }

    public function test_guest_cannot_check_a_nickname(): void
    {
        $this->get(route('products.nickname', ['brand' => 'free-fire', 'target' => '123']))
            ->assertRedirect(route('login'));
    }

    public function test_product_page_shows_the_nickname_check_for_a_supported_brand(): void
    {
        $product = Product::factory()
            ->for(Brand::factory()->create(['slug' => 'mobile-legends']))
            ->create(['status' => true]);

        $this->actingAs(User::factory()->create())
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('data-nickname-check', false)
            ->assertSee('Zone ID');
    }

    public function test_product_page_hides_the_nickname_check_for_an_unsupported_brand(): void
    {
        $product = Product::factory()
            ->for(Brand::factory()->create(['slug' => 'telkomsel']))
            ->create(['status' => true]);

        $this->actingAs(User::factory()->create())
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertDontSee('data-nickname-check', false);
    }
}
