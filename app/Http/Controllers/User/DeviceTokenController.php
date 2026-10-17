<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Registrasi & pencabutan token perangkat (FCM) milik user yang sedang login.
 *
 * Dipakai oleh push.js: setelah izin notifikasi diberikan, token FCM dikirim ke
 * sini; saat notifikasi dimatikan / logout token dihapus.
 */
class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);

        // Token unik per perangkat; bila sebelumnya milik user lain (mis. ganti
        // akun di perangkat yang sama), pindahkan kepemilikannya ke user ini.
        DeviceToken::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->getKey(),
                'platform' => $data['platform'] ?? 'web',
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
                'last_used_at' => now(),
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        DeviceToken::query()
            ->where('user_id', $request->user()->getKey())
            ->where('token', $data['token'])
            ->delete();

        return response()->json(['ok' => true]);
    }
}
