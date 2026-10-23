<?php

namespace App\Http\Controllers\Admin\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingTestimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function index(): View
    {
        return view('admin.landing.testimonials', [
            'testimonials' => LandingTestimonial::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LandingTestimonial::create($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Testimoni berhasil ditambahkan.');
    }

    public function update(Request $request, LandingTestimonial $testimonial): RedirectResponse
    {
        $testimonial->update($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Testimoni berhasil diperbarui.');
    }

    public function destroy(LandingTestimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return back()->with('status', 'Testimoni berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:1000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
