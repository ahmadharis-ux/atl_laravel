<?php

namespace Tests\Feature;

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FetchBrandLogosTest extends TestCase
{
    use RefreshDatabase;

    private function png(): string
    {
        $image = imagecreatetruecolor(8, 8);
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('brands');
        config()->set('brand-logos.min_bytes', 10);
    }

    public function test_a_brand_with_a_curated_domain_is_downloaded_without_a_lookup(): void
    {
        $png = $this->png();

        Http::fake([
            'unavatar.io/*' => Http::response('', 404),
            't3.gstatic.com/*' => Http::response($png, 200, ['Content-Type' => 'image/png']),
        ]);

        $brand = Brand::factory()->create(['slug' => 'telkomsel', 'name' => 'Telkomsel']);

        $this->artisan('brands:fetch-logos')->assertSuccessful();

        $this->assertSame('telkomsel.webp', $brand->fresh()->logo);
        Storage::disk('brands')->assertExists('telkomsel.webp');

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'autocomplete.clearbit.com'));
    }

    public function test_the_domain_is_resolved_from_the_brand_name_when_not_curated(): void
    {
        $png = $this->png();

        config()->set('brand-logos.overrides', []);

        Http::fake([
            'autocomplete.clearbit.com/*' => Http::response([
                ['name' => 'Acme Corp', 'domain' => 'acme.com'],
            ], 200),
            'unavatar.io/*' => Http::response('', 404),
            't3.gstatic.com/*' => Http::response($png, 200, ['Content-Type' => 'image/png']),
        ]);

        $brand = Brand::factory()->create(['name' => 'Acme Corp', 'slug' => 'acme-corp']);

        $this->artisan('brands:fetch-logos')->assertSuccessful();

        $this->assertSame('acme-corp.webp', $brand->fresh()->logo);
        Storage::disk('brands')->assertExists('acme-corp.webp');
    }

    public function test_a_brand_without_a_confident_match_is_skipped(): void
    {
        config()->set('brand-logos.overrides', []);

        Http::fake([
            'autocomplete.clearbit.com/*' => Http::response([
                ['name' => 'Totally Different', 'domain' => 'different.example'],
            ], 200),
        ]);

        $brand = Brand::factory()->create(['name' => 'Acme Corp', 'slug' => 'acme-corp']);

        $this->artisan('brands:fetch-logos')->assertSuccessful();

        $this->assertNull($brand->fresh()->logo);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 't3.gstatic.com'));
    }

    public function test_brands_that_already_have_a_logo_are_left_alone(): void
    {
        Http::fake();

        Brand::factory()->create(['slug' => 'telkomsel', 'name' => 'Telkomsel', 'logo' => 'telkomsel.png']);

        $this->artisan('brands:fetch-logos')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_logos_can_be_imported_from_a_local_directory(): void
    {
        $directory = sys_get_temp_dir().'/brand-logos-'.uniqid();
        File::makeDirectory($directory, 0755, true);
        File::put($directory.'/telkomsel.png', $this->png());

        $brand = Brand::factory()->create(['slug' => 'telkomsel', 'name' => 'Telkomsel']);

        try {
            $this->artisan('brands:fetch-logos', ['--from' => $directory])->assertSuccessful();
        } finally {
            File::deleteDirectory($directory);
        }

        $this->assertSame('telkomsel.webp', $brand->fresh()->logo);
        Storage::disk('brands')->assertExists('telkomsel.webp');
    }
}
