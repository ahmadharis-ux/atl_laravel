<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Merchandise;
use App\Support\MerchandiseImageStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MerchandiseController extends Controller
{
    use HasPerPage;

    public function index(Request $request): View|JsonResponse
    {
        $items = Merchandise::query()
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->query('status') === 'active'))
            ->orderByDesc('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $data = [
            'items' => $items,
            'listRoute' => route('admin.merchandise.index'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.merchandise._rows', $data)->render(),
                'footer' => view('admin.merchandise._footer', $data)->render(),
                'modals' => view('admin.merchandise._modals', $data)->render(),
            ]);
        }

        return view('admin.merchandise.index', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Merchandise::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'points_cost' => $data['points_cost'],
            'stock' => $data['stock'],
            'image' => $request->hasFile('image') ? MerchandiseImageStorage::store($request->file('image')) : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Merchandise berhasil ditambahkan.');
    }

    public function update(Request $request, Merchandise $merchandise): RedirectResponse
    {
        $data = $this->validated($request);

        $image = $merchandise->image;
        if ($request->hasFile('image')) {
            $image = MerchandiseImageStorage::store($request->file('image'), $merchandise->image);
        }

        $merchandise->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'points_cost' => $data['points_cost'],
            'stock' => $data['stock'],
            'image' => $image,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Merchandise berhasil diperbarui.');
    }

    public function destroy(Merchandise $merchandise): RedirectResponse
    {
        MerchandiseImageStorage::delete($merchandise->image);
        $merchandise->delete();

        return back()->with('status', 'Merchandise berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'points_cost' => ['required', 'integer', 'min:1'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'image.mimes' => 'Gambar harus berformat JPG, PNG, atau WEBP.',
            'image.max' => 'Ukuran gambar maksimal 2MB.',
            'points_cost.min' => 'Biaya poin minimal 1.',
            'stock.min' => 'Stok tidak boleh negatif.',
        ]);
    }
}
