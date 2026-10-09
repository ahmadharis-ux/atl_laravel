<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Support\GameNickname;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NicknameController extends Controller
{
    /**
     * Validasi nickname akun game lewat API publik (api.isan.eu.org).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'target' => ['required', 'string', 'max:50'],
            'zone' => ['nullable', 'string', 'max:20'],
        ]);

        return response()->json(
            GameNickname::make()->check(
                $validated['brand'],
                $validated['target'],
                $validated['zone'] ?? null,
            ),
        );
    }
}
