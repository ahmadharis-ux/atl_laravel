<?php

namespace App\Http\Controllers\Admin\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingFaq;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(): View
    {
        return view('admin.landing.faqs', [
            'faqs' => LandingFaq::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LandingFaq::create($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'FAQ berhasil ditambahkan.');
    }

    public function update(Request $request, LandingFaq $faq): RedirectResponse
    {
        $faq->update($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'FAQ berhasil diperbarui.');
    }

    public function destroy(LandingFaq $faq): RedirectResponse
    {
        $faq->delete();

        return back()->with('status', 'FAQ berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:200'],
            'answer' => ['required', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
