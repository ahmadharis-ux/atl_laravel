<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Support\ImageProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $brands = Brand::query()
            ->withCount('products')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', $term)->orWhere('slug', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $data = [
            'brands' => $brands,
            'listRoute' => route('admin.master-brands.index'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.brand._rows', $data)->render(),
                'footer' => view('admin.brand._footer', $data)->render(),
                'modals' => view('admin.brand._modals', $data)->render(),
            ]);
        }

        return view('admin.brand.index', $data);
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [], [
            'logo' => 'logo',
        ]);

        $disk = (string) config('brand-logos.disk', 'brands');
        $file = $request->file('logo');

        $webp = ImageProcessor::toWebp((string) $file->get(), 400, 400);
        $extension = $webp !== null ? 'webp' : strtolower((string) $file->extension());
        $path = $brand->slug.'.'.$extension;

        if ($brand->logo !== null && $brand->logo !== $path) {
            Storage::disk($disk)->delete($brand->logo);
        }

        if ($webp !== null) {
            Storage::disk($disk)->put($path, $webp);
        } else {
            $file->storeAs('/', $path, ['disk' => $disk]);
        }

        $brand->forceFill(['logo' => $path])->save();

        return back()->with('status', 'Logo '.$brand->name.' berhasil diperbarui.');
    }
}
