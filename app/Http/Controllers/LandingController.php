<?php

namespace App\Http\Controllers;

use App\Models\LandingFaq;
use App\Models\LandingService;
use App\Models\LandingSetting;
use App\Models\LandingTestimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * Landing page publik. Pengguna yang sudah login langsung diarahkan
     * ke dashboard sesuai perannya.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($user = $request->user()) {
            return redirect()->route($user->canAccessAdmin() ? 'admin.dashboard' : 'dashboard');
        }

        return view('landing.index', [
            'settings' => LandingSetting::current(),
            'services' => LandingService::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'testimonials' => LandingTestimonial::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'faqs' => LandingFaq::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
