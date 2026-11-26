<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ResellerKey;
use App\Support\ResellerWebhook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
    public function show(Request $request): Response
    {
        return response()->view('user.settings.reseller', [
            'user' => $request->user(),
            'tier' => $request->user()->tier(),
            'discountPercent' => $request->user()->priceDiscountPercent(),
            'termsVersion' => (string) config('reseller.terms_version'),
        ])->header('Cache-Control', 'private, no-store');
    }

    /**
     * Dokumentasi API (cara akses produk, API key, & transaksi).
     */
    public function docs(Request $request): View
    {
        return view('user.settings.reseller-docs', [
            'user' => $request->user(),
            'tier' => $request->user()->tier(),
            'discountPercent' => $request->user()->priceDiscountPercent(),
            'apiBase' => url('/api/v1/reseller'),
        ]);
    }

    /**
     * Kirim permohonan akses API untuk ditinjau admin.
     */
    public function activate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->is_reseller) {
            return back()->with('status', 'Akses API kamu sudah aktif.');
        }

        if ($user->reseller_status === 'pending') {
            return back()->with('status', 'Permohonan akses API kamu sedang menunggu peninjauan admin.');
        }

        if ($user->reseller_status === 'suspended') {
            return back()->with('error', 'Akses API sedang ditangguhkan. Hubungi admin untuk informasi lebih lanjut.');
        }

        $validated = $request->validate([
            'website' => ['required', 'url:http,https', 'max:255'],
            'contact_channel' => ['required', Rule::in(['telegram', 'whatsapp', 'other'])],
            'contact' => ['required', 'string', 'max:255'],
            'allowed_ips' => ['nullable', 'string', 'max:1000'],
            'accept_terms' => ['accepted'],
        ], [
            'website.required' => 'Domain/website wajib diisi untuk integrasi API.',
            'website.url' => 'Masukkan domain dengan format URL yang valid, misalnya https://contoh.com.',
            'contact_channel.required' => 'Pilih kanal kontak.',
            'contact_channel.in' => 'Kanal kontak yang dipilih tidak valid.',
            'contact.required' => 'Username, nomor, atau tautan kontak wajib diisi.',
            'accept_terms.accepted' => 'Kamu harus menyetujui ketentuan akses API sebelum mengirim permohonan.',
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
                    'reseller' => 'Akses API sedang ditangguhkan. Hubungi admin untuk informasi lebih lanjut.',
                ]);
            }

            $fromStatus = $account->reseller_status;
            $account->forceFill([
                'is_reseller' => false,
                'reseller_status' => 'pending',
                'reseller_website' => $validated['website'],
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
                'reason' => 'Permohonan akses API baru.',
                'terms_version' => $account->reseller_terms_version,
                'terms_accepted_at' => $account->reseller_terms_accepted_at,
            ]);

            return true;
        });

        return back()->with(
            'status',
            $submitted
                ? 'Permohonan akses API terkirim. Akses API akan aktif setelah ditinjau admin.'
                : 'Permohonan akses API kamu sedang menunggu peninjauan admin.'
        );
    }

    /**
     * Buat ulang API key (key lama langsung tidak berlaku).
     */
    public function regenerate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->is_reseller) {
            return back()->with('error', 'Akses API harus disetujui admin sebelum membuat API key.');
        }

        $key = DB::transaction(function () use ($user): array {
            $account = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (! $account->is_reseller || (! in_array($account->reseller_status, [null, 'active'], true))) {
                throw ValidationException::withMessages([
                    'reseller' => 'Akses API tidak lagi aktif. Muat ulang halaman.',
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

    public function updateWebhook(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->is_reseller || ! in_array($user->reseller_status, [null, 'active'], true)) {
            return back()->with('error', 'Pengaturan webhook hanya tersedia untuk akses API yang aktif.');
        }

        $validated = $request->validate([
            'webhook_url' => ['nullable', 'url:http,https', 'max:255'],
        ], [
            'webhook_url.url' => 'Masukkan URL webhook HTTP atau HTTPS yang valid.',
        ]);

        $webhookUrl = trim((string) ($validated['webhook_url'] ?? ''));

        if ($webhookUrl !== '' && ! ResellerWebhook::isDeliverableUrl($webhookUrl)) {
            throw ValidationException::withMessages([
                'webhook_url' => 'Gunakan URL publik yang aman. Alamat lokal atau jaringan internal tidak diizinkan.',
            ]);
        }

        DB::transaction(function () use ($user, $webhookUrl): void {
            $account = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (! $account->is_reseller || ! in_array($account->reseller_status, [null, 'active'], true)) {
                throw ValidationException::withMessages([
                    'reseller' => 'Akses API tidak lagi aktif. Muat ulang halaman.',
                ]);
            }

            $account->forceFill([
                'reseller_webhook_url' => $webhookUrl !== '' ? $webhookUrl : null,
            ])->save();
        });

        return back()->with(
            'status',
            $webhookUrl === ''
                ? 'Webhook dinonaktifkan.'
                : 'URL webhook berhasil disimpan.'
        );
    }

    public function regenerateWebhookSecret(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->is_reseller || ! in_array($user->reseller_status, [null, 'active'], true)) {
            return back()->with('error', 'Secret webhook hanya tersedia untuk akses API yang aktif.');
        }

        $secret = DB::transaction(function () use ($user): array {
            $account = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (! $account->is_reseller || ! in_array($account->reseller_status, [null, 'active'], true)) {
                throw ValidationException::withMessages([
                    'reseller' => 'Akses API tidak lagi aktif. Muat ulang halaman.',
                ]);
            }

            $secret = ResellerWebhook::generateSecret();
            $account->forceFill([
                'reseller_webhook_secret' => $secret['plain'],
                'reseller_webhook_secret_hint' => $secret['hint'],
            ])->save();

            return $secret;
        });

        return back()
            ->with('status', 'Secret webhook baru dibuat. Secret lama sudah tidak berlaku.')
            ->with('webhook_secret', $secret['plain']);
    }

    /**
     * Nonaktifkan keanggotaan reseller & cabut API key.
     */
    public function deactivate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->is_reseller) {
            return back()->with('error', 'Akses API kamu belum aktif.');
        }

        DB::transaction(function () use ($user): void {
            $account = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (! $account->is_reseller || (! in_array($account->reseller_status, [null, 'active'], true))) {
                throw ValidationException::withMessages([
                    'reseller' => 'Akses API tidak lagi aktif. Muat ulang halaman.',
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
                'reason' => 'Akses API dinonaktifkan oleh pengguna.',
                'terms_version' => $account->reseller_terms_version,
                'terms_accepted_at' => $account->reseller_terms_accepted_at,
            ]);
        });

        return back()->with(
            'status',
            'Akses API dinonaktifkan & API key dicabut. Kamu bisa mengaktifkan kembali kapan saja.'
        );
    }
}
