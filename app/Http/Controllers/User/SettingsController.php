<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        return view('user.settings.index', [
            'user' => $request->user(),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('user.settings.profile', [
            'user' => $request->user(),
        ]);
    }

    public function password(): View
    {
        return view('user.settings.password');
    }

    public function sessions(Request $request): View
    {
        return view('user.settings.sessions', [
            'sessions' => $this->activeSessions($request),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $emailChanged = $user->email !== $data['email'];

        $user->name = $data['name'];
        $user->phone = $data['phone'] ?? null;

        if ($emailChanged) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('status', 'Kata sandi berhasil diubah.');
    }

    public function logoutOtherDevices(Request $request): RedirectResponse
    {
        DB::table('sessions')
            ->where('user_id', $request->user()->getKey())
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('status', 'Berhasil keluar dari semua perangkat lain.');
    }

    /**
     * Daftar perangkat/sesi aktif dari tabel sessions (driver database).
     *
     * @return list<array{id: string, ip: ?string, device: string, browser: string, last_active: CarbonImmutable, is_current: bool}>
     */
    private function activeSessions(Request $request): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return DB::table('sessions')
            ->where('user_id', $request->user()->getKey())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (object $session): array => [
                'id' => $session->id,
                'ip' => $session->ip_address,
                'device' => $this->describeDevice($session->user_agent),
                'browser' => $this->describeBrowser($session->user_agent),
                'last_active' => CarbonImmutable::createFromTimestamp($session->last_activity),
                'is_current' => $session->id === $request->session()->getId(),
            ])
            ->all();
    }

    private function describeDevice(?string $agent): string
    {
        $agent = (string) $agent;

        return match (true) {
            str_contains($agent, 'iPhone') => 'iPhone',
            str_contains($agent, 'iPad') => 'iPad',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Perangkat tidak dikenal',
        };
    }

    private function describeBrowser(?string $agent): string
    {
        $agent = (string) $agent;

        return match (true) {
            str_contains($agent, 'Edg/') => 'Microsoft Edge',
            str_contains($agent, 'OPR/'), str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Chrome/') => 'Google Chrome',
            str_contains($agent, 'Firefox/') => 'Mozilla Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Browser tidak dikenal',
        };
    }
}
