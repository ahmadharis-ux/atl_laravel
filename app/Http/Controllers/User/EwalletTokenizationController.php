<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\EwalletTokenizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Scaffolding endpoint untuk menautkan akun e-wallet (Direct Debit).
 */
class EwalletTokenizationController extends Controller
{
    public function link(Request $request, EwalletTokenizationService $service): RedirectResponse
    {
        if (! $service->isEnabled()) {
            return back()->withErrors(['ewallet' => 'Fitur taut akun e-wallet belum tersedia.']);
        }

        abort(501, 'Fitur taut akun e-wallet belum diimplementasikan.');
    }
}
