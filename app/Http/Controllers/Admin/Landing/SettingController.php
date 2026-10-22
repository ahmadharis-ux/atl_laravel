<?php

namespace App\Http\Controllers\Admin\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.landing.settings', [
            'setting' => LandingSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero_badge' => ['nullable', 'string', 'max:60'],
            'hero_title' => ['required', 'string', 'max:150'],
            'hero_subtitle' => ['nullable', 'string', 'max:1000'],
            'hero_cta_label' => ['nullable', 'string', 'max:50'],
            'hero_cta_url' => ['nullable', 'string', 'max:255'],
            'hero_secondary_label' => ['nullable', 'string', 'max:50'],
            'hero_secondary_url' => ['nullable', 'string', 'max:255'],
            'about_title' => ['nullable', 'string', 'max:150'],
            'about_body' => ['nullable', 'string', 'max:2000'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'stat_customers' => ['nullable', 'string', 'max:30'],
            'stat_transactions' => ['nullable', 'string', 'max:30'],
            'stat_products' => ['nullable', 'string', 'max:30'],
            'stat_support' => ['nullable', 'string', 'max:30'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ], [
            'hero_title.required' => 'Judul hero wajib diisi.',
        ]);

        LandingSetting::current()->update($validated);

        return back()->with('status', 'Konten landing page berhasil diperbarui.');
    }
}
