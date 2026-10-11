<?php

namespace Tests\Feature;

use App\Enums\MarkupType;
use App\Enums\ProductPaymentType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PulsaPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('pulsa.index'))->assertRedirect(route('login'));
    }

    public function test_page_prompts_for_number_before_showing_packages(): void
    {
        $pulsa = Category::factory()->create(['slug' => 'pulsa', 'name' => 'Pulsa']);
        $brand = Brand::factory()->create(['slug' => 'telkomsel', 'name' => 'Telkomsel']);
        Product::factory()->create([
            'category_id' => $pulsa->getKey(),
            'brand_id' => $brand->getKey(),
            'payment_type' => ProductPaymentType::Prabayar,
            'status' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('pulsa.index'))
            ->assertOk()
            ->assertSee('data-operator-number', false)
            ->assertSee('Masukkan nomor HP dulu', false);
    }

    public function test_pulsa_tab_shows_only_the_detected_operator_packages(): void
    {
        $pulsa = Category::factory()->create(['slug' => 'pulsa', 'name' => 'Pulsa']);
        $telkomsel = Brand::factory()->create(['slug' => 'telkomsel', 'name' => 'Telkomsel']);
        $xl = Brand::factory()->create(['slug' => 'xl', 'name' => 'XL']);

        Product::factory()->create([
            'name' => 'Pulsa Telkomsel 10 Ribu',
            'category_id' => $pulsa->getKey(),
            'brand_id' => $telkomsel->getKey(),
            'status' => true,
        ]);
        Product::factory()->create([
            'name' => 'Pulsa XL 10 Ribu',
            'category_id' => $pulsa->getKey(),
            'brand_id' => $xl->getKey(),
            'status' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('pulsa.index', ['tab' => 'pulsa', 'brand' => $telkomsel->getKey()]))
            ->assertOk()
            ->assertSee('Pulsa Telkomsel 10 Ribu')
            ->assertDontSee('Pulsa XL 10 Ribu')
            ->assertDontSee('Masukkan nomor HP dulu', false);
    }

    public function test_data_tab_shows_data_packages_for_the_operator(): void
    {
        $data = Category::factory()->create(['slug' => 'data', 'name' => 'Paket Data']);
        $telkomsel = Brand::factory()->create(['slug' => 'telkomsel', 'name' => 'Telkomsel']);

        Product::factory()->create([
            'name' => 'Telkomsel 10GB 30 Hari',
            'category_id' => $data->getKey(),
            'brand_id' => $telkomsel->getKey(),
            'status' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('pulsa.index', ['tab' => 'data', 'brand' => $telkomsel->getKey()]))
            ->assertOk()
            ->assertSee('Telkomsel 10GB 30 Hari');
    }

    public function test_page_shows_price_filter_and_sort_controls(): void
    {
        Category::factory()->create(['slug' => 'pulsa', 'name' => 'Pulsa']);
        Category::factory()->create(['slug' => 'data', 'name' => 'Paket Data']);

        $this->actingAs(User::factory()->create())
            ->get(route('pulsa.index'))
            ->assertOk()
            ->assertSee('data-modal-open="modalFilter"', false)
            ->assertSeeInOrder(['id="modalFilter"', 'name="sort"', 'name="min_price"', 'name="max_price"'], false);
    }

    public function test_packages_can_be_filtered_and_sorted_by_price(): void
    {
        $pulsa = Category::factory()->create(['slug' => 'pulsa', 'name' => 'Pulsa']);
        $brand = Brand::factory()->create(['slug' => 'telkomsel', 'name' => 'Telkomsel']);

        Product::factory()->create([
            'name' => 'Pulsa Murah',
            'category_id' => $pulsa->getKey(),
            'brand_id' => $brand->getKey(),
            'payment_type' => ProductPaymentType::Prabayar,
            'status' => true,
            'cost_price' => 10_000, 'markup_type' => MarkupType::Fixed, 'markup_value' => 0,
        ]);
        Product::factory()->create([
            'name' => 'Pulsa Mahal',
            'category_id' => $pulsa->getKey(),
            'brand_id' => $brand->getKey(),
            'payment_type' => ProductPaymentType::Prabayar,
            'status' => true,
            'cost_price' => 100_000, 'markup_type' => MarkupType::Fixed, 'markup_value' => 0,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pulsa.index', ['tab' => 'pulsa', 'brand' => $brand->getKey(), 'max_price' => 50_000]))
            ->assertOk()
            ->assertSee('Pulsa Murah')
            ->assertDontSee('Pulsa Mahal');

        $this->actingAs($user)
            ->get(route('pulsa.index', ['tab' => 'pulsa', 'brand' => $brand->getKey(), 'sort' => 'price_desc']))
            ->assertOk()
            ->assertSeeInOrder(['Pulsa Mahal', 'Pulsa Murah']);

        $this->actingAs($user)
            ->get(route('pulsa.index', ['tab' => 'pulsa', 'brand' => $brand->getKey(), 'min_price' => 500_000]))
            ->assertOk()
            ->assertSee('Tidak ada paket sesuai filter');
    }

    public function test_dashboard_links_pulsa_and_data_cards_to_the_new_page(): void
    {
        Category::factory()->create(['slug' => 'pulsa', 'name' => 'Pulsa']);
        Category::factory()->create(['slug' => 'data', 'name' => 'Paket Data']);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('pulsa.index', ['tab' => 'pulsa']), false)
            ->assertSee(route('pulsa.index', ['tab' => 'data']), false);
    }
}
