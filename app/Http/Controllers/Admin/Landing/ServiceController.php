<?php

namespace App\Http\Controllers\Admin\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.landing.services', [
            'services' => LandingService::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LandingService::create($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Layanan landing page berhasil ditambahkan.');
    }

    public function update(Request $request, LandingService $service): RedirectResponse
    {
        $service->update($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Layanan landing page berhasil diperbarui.');
    }

    public function destroy(LandingService $service): RedirectResponse
    {
        $service->delete();

        return back()->with('status', 'Layanan landing page berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'icon' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
