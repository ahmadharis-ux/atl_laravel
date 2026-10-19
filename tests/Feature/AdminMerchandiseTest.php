<?php

namespace Tests\Feature;

use App\Models\Merchandise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminMerchandiseTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['dashboard.view', 'merchandise.view', 'merchandise.update']): User
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

    public function test_regular_users_cannot_access_the_merchandise_admin_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.merchandise.index'))
            ->assertForbidden();
    }

    public function test_admin_without_update_permission_cannot_create_merchandise(): void
    {
        $admin = $this->admin(['dashboard.view', 'merchandise.view']);

        $this->actingAs($admin)
            ->post(route('admin.merchandise.store'), [
                'name' => 'Tumbler',
                'points_cost' => 100,
                'stock' => 5,
            ])
            ->assertForbidden();

        $this->assertSame(0, Merchandise::count());
    }

    public function test_admin_can_create_merchandise(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.merchandise.store'), [
                'name' => 'Tumbler Atlantic',
                'description' => 'Tumbler edisi terbatas',
                'points_cost' => 250,
                'stock' => 8,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $item = Merchandise::first();
        $this->assertNotNull($item);
        $this->assertSame('Tumbler Atlantic', $item->name);
        $this->assertSame(250, (int) $item->points_cost);
        $this->assertSame(8, $item->stock);
        $this->assertTrue($item->is_active);
    }

    public function test_admin_can_update_merchandise(): void
    {
        $admin = $this->admin();
        $item = Merchandise::factory()->create(['name' => 'Lama', 'points_cost' => 50, 'stock' => 1]);

        $this->actingAs($admin)
            ->put(route('admin.merchandise.update', $item), [
                'name' => 'Baru',
                'points_cost' => 120,
                'stock' => 4,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $item->refresh();
        $this->assertSame('Baru', $item->name);
        $this->assertSame(120, (int) $item->points_cost);
        $this->assertSame(4, $item->stock);
    }

    public function test_admin_can_delete_merchandise(): void
    {
        $admin = $this->admin();
        $item = Merchandise::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.merchandise.destroy', $item))
            ->assertRedirect();

        $this->assertNull(Merchandise::find($item->getKey()));
    }

    public function test_admin_can_upload_merchandise_image(): void
    {
        Storage::fake('merchandise');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.merchandise.store'), [
                'name' => 'Dengan Gambar',
                'points_cost' => 100,
                'stock' => 1,
                'image' => UploadedFile::fake()->image('merch.jpg', 200, 200),
                'is_active' => '1',
            ])
            ->assertRedirect();

        $item = Merchandise::first();
        $this->assertNotNull($item);
        $this->assertNotNull($item->image);
        Storage::disk('merchandise')->assertExists($item->image);
    }
}
