<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_postpaid_products_without_choosing_the_tab(): void
    {
        $user = User::factory()->create();
        Product::factory()->pascabayar()->create(['name' => 'Tagihan PDAM', 'status' => true]);

        $this->actingAs($user)->get('/produk?q=pdam')
            ->assertOk()
            ->assertSee('Tagihan PDAM')
            ->assertDontSee('Produk tidak ditemukan');
    }

    public function test_search_ignores_the_active_payment_type_tab(): void
    {
        $user = User::factory()->create();
        Product::factory()->pascabayar()->create(['name' => 'Tagihan PDAM', 'status' => true]);

        $this->actingAs($user)->get('/produk?q=pdam&payment_type=prabayar')
            ->assertOk()
            ->assertSee('Tagihan PDAM');
    }

    public function test_browsing_without_a_query_filters_by_payment_type(): void
    {
        $user = User::factory()->create();
        Product::factory()->prabayar()->create(['name' => 'Pulsa Telkomsel', 'status' => true]);
        Product::factory()->pascabayar()->create(['name' => 'Tagihan PDAM', 'status' => true]);

        $this->actingAs($user)->get('/produk')
            ->assertOk()
            ->assertSee('Pulsa Telkomsel')
            ->assertDontSee('Tagihan PDAM');
    }

    public function test_ajax_search_returns_tabs_fragment(): void
    {
        $user = User::factory()->create();
        Product::factory()->pascabayar()->create(['name' => 'Tagihan PDAM', 'status' => true]);

        $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('/produk?q=pdam')
            ->assertOk()
            ->assertJsonStructure(['rows', 'tabs', 'footer'])
            ->assertJsonPath('tabs', fn (string $tabs): bool => str_contains($tabs, 'Semua jenis'));
    }
}
