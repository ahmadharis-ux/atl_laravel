<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DashboardBanner;
use App\Support\BannerImageStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardBannerController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $banners = DashboardBanner::query()
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('title', 'like', $term)
                        ->orWhere('subtitle', 'like', $term)
                        ->orWhere('badge', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->query('status') === 'active'))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $data = [
            'banners' => $banners,
            'listRoute' => route('admin.dashboard-banners.index'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.dashboard-banner._rows', $data)->render(),
                'footer' => view('admin.dashboard-banner._footer', $data)->render(),
                'modals' => view('admin.dashboard-banner._modals', $data)->render(),
            ]);
        }

        return view('admin.dashboard-banner.index', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DashboardBanner::create([
            'badge' => $data['badge'] ?? null,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'description' => $data['description'] ?? null,
            'image' => $request->hasFile('image') ? BannerImageStorage::store($request->file('image')) : null,
            'link' => $data['link'] ?? null,
            'cta_label' => $data['cta_label'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        return back()->with('status', 'Banner dashboard berhasil ditambahkan.');
    }

    public function update(Request $request, DashboardBanner $dashboardBanner): RedirectResponse
    {
        $data = $this->validated($request);

        $image = $dashboardBanner->image;
        if ($request->hasFile('image')) {
            $image = BannerImageStorage::store($request->file('image'), $dashboardBanner->image);
        }

        $dashboardBanner->update([
            'badge' => $data['badge'] ?? null,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'description' => $data['description'] ?? null,
            'image' => $image,
            'link' => $data['link'] ?? null,
            'cta_label' => $data['cta_label'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        return back()->with('status', 'Banner dashboard berhasil diperbarui.');
    }

    public function destroy(DashboardBanner $dashboardBanner): RedirectResponse
    {
        BannerImageStorage::delete($dashboardBanner->image);
        $dashboardBanner->delete();

        return back()->with('status', 'Banner dashboard berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'badge' => ['nullable', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'link' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ], [
            'image.mimes' => 'Gambar harus berformat JPG, PNG, atau WEBP.',
            'image.max' => 'Ukuran gambar maksimal 2MB.',
            'ends_at.after_or_equal' => 'Tanggal berakhir harus setelah tanggal mulai.',
        ]);
    }
}
