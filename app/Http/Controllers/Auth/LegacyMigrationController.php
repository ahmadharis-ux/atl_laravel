<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LegacyUser;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class LegacyMigrationController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.legacy-login');
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'identifier.required' => 'Username atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $legacyUser = LegacyUser::query()
            ->whereNull('migrated_at')
            ->where(function ($query) use ($validated): void {
                $query->where('email', $validated['identifier'])
                    ->orWhere('username', $validated['identifier']);
            })
            ->first();

        if ($legacyUser === null || ! Hash::check($validated['password'], $legacyUser->password_hash)) {
            throw ValidationException::withMessages([
                'identifier' => 'Data akun lama tidak cocok atau akun sudah dimigrasikan.',
            ]);
        }

        if (! filled($legacyUser->email)) {
            throw ValidationException::withMessages([
                'identifier' => 'Akun lama ini belum memiliki email sehingga belum dapat dimigrasikan.',
            ]);
        }

        if (User::query()->where('email', $legacyUser->email)->exists()) {
            throw ValidationException::withMessages([
                'identifier' => 'Email akun lama sudah digunakan di aplikasi baru. Hubungi admin.',
            ]);
        }

        $request->session()->put('legacy_migration_id', $legacyUser->getKey());

        return redirect()->route('legacy-migration.confirm');
    }

    public function confirm(Request $request): View|RedirectResponse
    {
        $legacyUser = $this->pendingUser($request);

        if ($legacyUser === null) {
            return redirect()->route('legacy-migration.login')
                ->with('error', 'Sesi migrasi tidak ditemukan. Silakan ulangi.');
        }

        return view('auth.legacy-confirm', ['legacyUser' => $legacyUser]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $request->validate([
            'agree' => ['accepted'],
        ], [
            'agree.accepted' => 'Kamu harus menyetujui reset data lama sebelum melanjutkan.',
        ]);

        $legacyId = $request->session()->get('legacy_migration_id');
        $user = DB::transaction(function () use ($legacyId): User {
            $legacyUser = LegacyUser::query()->lockForUpdate()->find($legacyId);

            if ($legacyUser === null || $legacyUser->migrated_at !== null) {
                throw ValidationException::withMessages([
                    'agree' => 'Akun lama sudah dimigrasikan atau tidak tersedia.',
                ]);
            }

            if (User::query()->where('email', $legacyUser->email)->exists()) {
                throw ValidationException::withMessages([
                    'agree' => 'Email akun lama sudah digunakan di aplikasi baru. Hubungi admin.',
                ]);
            }

            $user = new User;
            $user->forceFill([
                'name' => filled($legacyUser->username) ? $legacyUser->username : $legacyUser->email,
                'email' => $legacyUser->email,
                'is_admin' => false,
                'password' => $legacyUser->password_hash,
                'email_verified_at' => now(),
                'balance' => (int) round((float) $legacyUser->balance),
                'points' => (int) $legacyUser->points,
                'pin_hash' => null,
                'pin_set_at' => null,
                'pin_attempts' => 0,
                'pin_locked_until' => null,
                'pin_reset_at' => null,
            ]);
            $user->save();
            $user->assignRole(Role::findOrCreate(Roles::USER, 'web'));

            $legacyUser->forceFill([
                'migrated_at' => now(),
                'migrated_user_id' => $user->getKey(),
            ])->save();

            return $user;
        });

        $request->session()->forget('legacy_migration_id');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('settings.pin')
            ->with('status', 'Data akun lama berhasil dipindahkan. Atur PIN transaksi untuk melanjutkan.');
    }

    private function pendingUser(Request $request): ?LegacyUser
    {
        $id = $request->session()->get('legacy_migration_id');

        return is_numeric($id)
            ? LegacyUser::query()->whereKey((int) $id)->whereNull('migrated_at')->first()
            : null;
    }
}
