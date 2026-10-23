<?php

namespace Tests\Feature;

use App\Enums\MarkupType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminProductBulkMarkupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['dashboard.view', 'products.view', 'products.update']): User
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

    public function test_regular_users_cannot_bulk_update_markup(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.bulk-markup'), [
                'product_ids' => [$product->getKey()],
                'markup_type' => MarkupType::Fixed->value,
                'markup_value' => 1_000,
            ])
            ->assertForbidden();
    }

    public function test_admin_without_update_permission_cannot_bulk_update_markup(): void
    {
        $admin = $this->admin(['dashboard.view', 'products.view']);
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.bulk-markup'), [
                'product_ids' => [$product->getKey()],
                'markup_type' => MarkupType::Fixed->value,
                'markup_value' => 1_000,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_apply_a_fixed_markup_to_selected_products_only(): void
    {
        $admin = $this->admin();
        $first = Product::factory()->create(['markup_type' => MarkupType::Percent, 'markup_value' => 10]);
        $second = Product::factory()->create(['markup_type' => MarkupType::Percent, 'markup_value' => 20]);
        $untouched = Product::factory()->create(['markup_type' => MarkupType::Fixed, 'markup_value' => 500]);

        $this->actingAs($admin)
            ->post(route('admin.products.bulk-markup'), [
                'product_ids' => [$first->getKey(), $second->getKey()],
                'markup_type' => MarkupType::Fixed->value,
                'markup_value' => 2_500,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Markup berhasil diperbarui untuk 2 produk.');

        foreach ([$first, $second] as $product) {
            $product->refresh();
            $this->assertSame(MarkupType::Fixed, $product->markup_type);
            $this->assertEquals(2_500, $product->markup_value);
        }

        $untouched->refresh();
        $this->assertSame(MarkupType::Fixed, $untouched->markup_type);
        $this->assertEquals(500, $untouched->markup_value);
    }

    public function test_admin_can_apply_a_percent_markup(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['markup_type' => MarkupType::Fixed, 'markup_value' => 1_000]);

        $this->actingAs($admin)
            ->post(route('admin.products.bulk-markup'), [
                'product_ids' => [$product->getKey()],
                'markup_type' => MarkupType::Percent->value,
                'markup_value' => 7.5,
            ])
            ->assertRedirect();

        $product->refresh();
        $this->assertSame(MarkupType::Percent, $product->markup_type);
        $this->assertEquals(7.5, $product->markup_value);
    }

    public function test_product_ids_are_required(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.products.bulk-markup'), [
                'product_ids' => [],
                'markup_type' => MarkupType::Fixed->value,
                'markup_value' => 1_000,
            ])
            ->assertSessionHasErrors('product_ids');
    }

    public function test_each_product_id_must_exist(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.products.bulk-markup'), [
                'product_ids' => [999_999],
                'markup_type' => MarkupType::Fixed->value,
                'markup_value' => 1_000,
            ])
            ->assertSessionHasErrors('product_ids.0');
    }

    public function test_markup_type_must_be_valid(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.bulk-markup'), [
                'product_ids' => [$product->getKey()],
                'markup_type' => 'invalid',
                'markup_value' => 1_000,
            ])
            ->assertSessionHasErrors('markup_type');
    }

    public function test_markup_value_must_be_numeric_and_not_negative(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.bulk-markup'), [
                'product_ids' => [$product->getKey()],
                'markup_type' => MarkupType::Fixed->value,
                'markup_value' => -1,
            ])
            ->assertSessionHasErrors('markup_value');
    }

    public function test_product_listing_renders_the_bulk_markup_controls(): void
    {
        $admin = $this->admin();
        Product::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.prepay.index'))
            ->assertOk()
            ->assertSee('Update Markup')
            ->assertSee('modalBulkMarkup', false)
            ->assertSee('row-check', false)
            ->assertSee('data-bulk-ids', false);
    }
}
