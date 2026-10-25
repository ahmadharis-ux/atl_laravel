<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ResellerKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResellerController extends Controller
{
    /**
     * Panel reseller: status keanggotaan, API key, benefit, dan aksi.
     */
    public function show(Request $request): View
    {
        return view('user.settings.reseller', [
            'user' => $request->user(),
            'discountPercent' => (float) config('reseller.discount_percent', 0),
            'termsVersion' => (string) config('reseller.terms_version'),
        ]);
    }

    /**
     * Dokumentasi API reseller (cara akses produk, API key, & transaksi).
     */
    public function docs(Request $request): View
    {
        return view('user.settings.reseller-docs', [
            'user' => $request->user(),
            'discountPercent' => (float) config('reseller.discount_percent', 0),
            'apiBase' => url('/api/v1/reseller'),
        ]);
    }

    /**
     * Kirim permohonan reseller untuk ditinjau admin.
     */
    public function activate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->is_reseller) {
            return back()->with('status', 'Akun kamu sudah aktif sebagai reseller.');
        }

        if ($user->reseller_status === 'pending') {
            return back()->with('status', 'Permohonan reseller kamu sedang menunggu peninjauan admin.');
        }

        if ($user->reseller_status === 'suspended') {
            return back()->with('error', 'Keanggotaan reseller sedang ditangguhkan. Hubungi admin untuk informasi lebih lanjut.');
        }

        $validated = $request->validate([
            'website' => [
                in_array($request->input('sales_channel'), ['api', 'both'], true) ? 'required' : 'nullable',
                'url:http,https',
                'max:255',
            ],
            'sales_channel' => ['required', Rule::in(['hosted', 'api', 'both'])],
            'contact_channel' => ['required', Rule::in(['telegram', 'whatsapp', 'other'])],
            'contact' => ['required', 'string', 'max:255'],
            'allowed_ips' => ['nullable', 'string', 'max:1000'],
            'accept_terms' => ['accepted'],
        ], [
            'website.required' => 'Domain wajib diisi jika kamu memilih integrasi API.',
            'website.url' => 'Masukkan domain dengan format URL yang valid, misalnya https://contoh.com.',
            'sales_channel.required' => 'Pilih cara berjualan.',
            'sales_channel.in' => 'Cara berjualan yang dipilih tidak valid.',
            'contact_channel.required' => 'Pilih kanal kontak.',
            'contact_channel.in' => 'Kanal kontak yang dipilih tidak valid.',
            'contact.required' => 'Username, nomor, atau tautan kontak wajib diisi.',
            'accept_terms.accepted' => 'Kamu harus menyetujui ketentuan reseller sebelum mengirim permohonan.',
            'allowed_ips.max' => 'IP server maksimal 20 alamat.',
        ]);

        $allowedIps = preg_split('/[\s,]+/', trim((string) ($validated['allowed_ips'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ipValidator = Validator::make(['allowed_ips' => $allowedIps], [
            'allowed_ips' => ['array', 'max:20'],
            'allowed_ips.*' => ['ip'],
        ], [
            'allowed_ips.*.ip' => 'Setiap alamat IP harus berupa IPv4 atau IPv6 yang valid.',
            'allowed_ips.max' => 'IP server maksimal 20 alamat.',
        ]);

        if ($ipValidator->fails()) {
            throw ValidationException::withMessages([
                'allowed_ips' => $ipValidator->errors()->first(),
            ]);
        }
        $allowedIps = array_values(array_unique($allowedIps));

        $submitted = DB::transaction(function () use ($user, $validated, $allowedIps): bool {
            $account = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if ($account->is_reseller || $account->reseller_status === 'pending') {
                return false;
            }

            if ($account->reseller_status === 'suspended') {
                throw ValidationException::withMessages([
                    'reseller' => 'Keanggotaan reseller sedang ditangguhkan. Hubungi admin untuk informasi lebih lanjut.',
                ]);
            }

            $fromStatus = $account->reseller_status;
            $account->forceFill([
                'is_reseller' => false,
                'reseller_status' => 'pending',
                'reseller_website' => $validated['website'] ?? null,
                'reseller_sales_channel' => $validated['sales_channel'],
                'reseller_contact_channel' => $validated['contact_channel'],
                'reseller_contact' => trim($validated['contact']),
                'reseller_allowed_ips' => $allowedIps,
                'reseller_terms_version' => (string) config('reseller.terms_version'),
                'reseller_terms_accepted_at' => now(),
                'reseller_status_reason' => null,
                'reseller_reviewed_at' => null,
                'reseller_reviewed_by' => null,
                'reseller_api_key' => null,
                'reseller_api_key_hint' => null,
            ])->save();

            $account->resellerStatusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => 'pending',
                'reason' => 'Permohonan reseller baru.',
                'terms_version' => $account->reseller_terms_version,
                'terms_accepted_at' => $account->reseller_terms_accepted_at,
            ]);

            return true;
        });

        return back()->with(
            'status',
            $submitted
                ? 'Permohonan reseller terkirim. Akses toko dan API akan aktif setelah ditinjau admin.'
                : 'Permohonan reseller kamu sedang menunggu peninjauan admin.'
        );
    }

    /**
     * Buat ulang API key (key lama langsung tidak berlaku).
     */
    public function regenerate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->is_reseller) {
            return back()->with('error', 'Keanggotaan reseller harus disetujui admin sebelum membuat API key.');
        }

        $key = DB::transaction(function () use ($user): array {
            $account = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (! $account->is_reseller || $account->reseller_status !== 'active') {
                throw ValidationException::withMessages([
                    'reseller' => 'Keanggotaan reseller tidak lagi aktif. Muat ulang halaman.',
                ]);
            }

            $key = ResellerKey::generate();
            $account->forceFill([
                'reseller_api_key' => $key['hash'],
                'reseller_api_key_hint' => $key['hint'],
            ])->save();

            return $key;
        });

        return back()
            ->with('status', 'API key baru dibuat. Key lama sudah tidak berlaku — salin key baru sekarang.')
            ->with('api_key', $key['plain']);
    }

    /**
     * Nonaktifkan keanggotaan reseller & cabut API key.
     */
    public function deactivate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->is_reseller) {
            return back()->with('error', 'Akun kamu bukan reseller aktif.');
        }

        DB::transaction(function () use ($user): void {
            $account = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (! $account->is_reseller || $account->reseller_status !== 'active') {
                throw ValidationException::withMessages([
                    'reseller' => 'Keanggotaan reseller tidak lagi aktif. Muat ulang halaman.',
                ]);
            }

            $fromStatus = $account->reseller_status ?? 'active';
            $account->forceFill([
                'is_reseller' => false,
                'reseller_status' => 'inactive',
                'reseller_api_key' => null,
                'reseller_api_key_hint' => null,
            ])->save();
            $account->resellerStatusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => 'inactive',
                'reason' => 'Keanggotaan dinonaktifkan oleh reseller.',
                'terms_version' => $account->reseller_terms_version,
                'terms_accepted_at' => $account->reseller_terms_accepted_at,
            ]);
        });

        return back()->with(
            'status',
            'Keanggotaan reseller dinonaktifkan & API key dicabut. Kamu bisa mengaktifkan kembali kapan saja.'
        );
    }
}
