<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\LoginBanner;
use App\Support\BannerImageStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginBannerController extends Controller
{
    use HasPerPage;

    public function index(Request $request): View|JsonResponse
    {
        $banners = LoginBanner::query()
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('title', 'like', $term)->orWhere('body', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->query('status') === 'active'))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $data = [
            'banners' => $banners,
            'listRoute' => route('admin.login-banners.index'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.login-banner._rows', $data)->render(),
                'footer' => view('admin.login-banner._footer', $data)->render(),
                'modals' => view('admin.login-banner._modals', $data)->render(),
            ]);
        }

        return view('admin.login-banner.index', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        LoginBanner::create([
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'image' => $request->hasFile('image') ? BannerImageStorage::store($request->file('image')) : null,
            'link' => $data['link'] ?? null,
            'cta_label' => $data['cta_label'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        return back()->with('status', 'Banner login berhasil ditambahkan.');
    }

    public function update(Request $request, LoginBanner $loginBanner): RedirectResponse
    {
        $data = $this->validated($request);

        $image = $loginBanner->image;
        if ($request->hasFile('image')) {
            $image = BannerImageStorage::store($request->file('image'), $loginBanner->image);
        }

        $loginBanner->update([
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'image' => $image,
            'link' => $data['link'] ?? null,
            'cta_label' => $data['cta_label'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        return back()->with('status', 'Banner login berhasil diperbarui.');
    }

    public function destroy(LoginBanner $loginBanner): RedirectResponse
    {
        BannerImageStorage::delete($loginBanner->image);
        $loginBanner->delete();

        return back()->with('status', 'Banner login berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:2000'],
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
