<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminBrandTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['dashboard.view', 'master-brands.view', 'master-brands.update']): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create(['is_admin' => true]);
        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_admin_can_view_the_master_brand_page(): void
    {
        Brand::factory()->create(['name' => 'Telkomsel']);

        $this->actingAs($this->admin())
            ->get(route('admin.master-brands.index'))
            ->assertOk()
            ->assertSee('Master Brand')
            ->assertSee('Telkomsel');
    }

    public function test_admin_can_upload_a_brand_logo(): void
    {
        Storage::fake('brands');

        $brand = Brand::factory()->create(['slug' => 'telkomsel']);

        $this->actingAs($this->admin())
            ->put(route('admin.master-brands.update', $brand), [
                'logo' => UploadedFile::fake()->image('logo.png', 128, 128),
            ])
            ->assertSessionHas('status');

        $brand->refresh();
        $this->assertSame('telkomsel.webp', $brand->logo);
        Storage::disk('brands')->assertExists('telkomsel.webp');
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        Storage::fake('brands');

        $brand = Brand::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.master-brands.update', $brand), [
                'logo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');

        $this->assertNull($brand->fresh()->logo);
    }

    public function test_admin_without_update_permission_cannot_upload_a_logo(): void
    {
        Storage::fake('brands');

        $brand = Brand::factory()->create();

        $this->actingAs($this->admin(['dashboard.view', 'master-brands.view']))
            ->put(route('admin.master-brands.update', $brand), [
                'logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertForbidden();
    }
}
