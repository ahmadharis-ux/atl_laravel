<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResellerController extends Controller
{
    use HasPerPage;

    /**
     * Laporan pengguna yang memilih menjadi reseller + statistiknya.
     */
    public function index(Request $request): View|JsonResponse
    {
        $items = User::query()
            ->whereNotNull('reseller_status')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('reseller_status', $request->query('status')))
            ->with('resellerReviewer:id,name')
            ->with(['resellerStatusHistories' => fn ($query) => $query->with('actor:id,name')->latest()->limit(5)])
            ->withCount(['orders', 'orders as api_orders_count' => fn ($query) => $query->where('source', 'api')])
            ->withSum(['orders as api_amount_sum' => fn ($query) => $query->where('source', 'api')], 'amount')
            ->orderByRaw('COALESCE(reseller_terms_accepted_at, reseller_since) DESC')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $data = [
            'items' => $items,
            'listRoute' => route('admin.resellers.index'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.reseller._rows', $data)->render(),
                'footer' => view('admin.reseller._footer', $data)->render(),
            ]);
        }

        $data['stats'] = [
            'totalResellers' => User::query()->whereNotNull('reseller_status')->count(),
            'activeResellers' => User::query()->where('reseller_status', 'active')->count(),
            'apiOrders' => Order::query()->where('source', 'api')->count(),
            'apiAmount' => (int) Order::query()->where('source', 'api')->sum('amount'),
        ];

        return view('admin.reseller.index', $data);
    }

    public function updateStatus(Request $request, User $reseller): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'suspend', 'reactivate'])],
            'reason' => [
                in_array($request->input('action'), ['reject', 'suspend'], true) ? 'required' : 'nullable',
                'string',
                'not_regex:/\A\s*\z/',
                'max:1000',
            ],
        ], [
            'reason.required' => 'Alasan wajib diisi untuk menolak atau menangguhkan reseller.',
            'reason.not_regex' => 'Alasan tidak boleh hanya berisi spasi.',
            'reason.max' => 'Alasan maksimal 1000 karakter.',
        ]);

        DB::transaction(function () use ($request, $reseller, $validated): void {
            $account = User::query()->whereKey($reseller->getKey())->lockForUpdate()->firstOrFail();
            $fromStatus = $account->reseller_status;
            $allowedTransitions = [
                'approve' => ['pending', 'active'],
                'reject' => ['pending', 'rejected'],
                'suspend' => ['active', 'suspended'],
                'reactivate' => ['suspended', 'active'],
            ];
            [$requiredStatus, $nextStatus] = $allowedTransitions[$validated['action']];

            if ($fromStatus !== $requiredStatus) {
                throw ValidationException::withMessages([
                    'status' => 'Status reseller sudah berubah. Muat ulang halaman sebelum mencoba lagi.',
                ]);
            }

            $isActive = $nextStatus === 'active';
            $reason = in_array($validated['action'], ['reject', 'suspend'], true)
                ? trim($validated['reason'])
                : null;

            $account->forceFill([
                'is_reseller' => $isActive,
                'reseller_status' => $nextStatus,
                'reseller_since' => $isActive ? ($account->reseller_since ?? now()) : $account->reseller_since,
                'reseller_api_key' => null,
                'reseller_api_key_hint' => null,
                'reseller_status_reason' => $reason,
                'reseller_reviewed_at' => now(),
                'reseller_reviewed_by' => $request->user()->getKey(),
            ])->save();

            $account->resellerStatusHistories()->create([
                'actor_id' => $request->user()->getKey(),
                'from_status' => $fromStatus,
                'to_status' => $nextStatus,
                'reason' => $reason,
                'terms_version' => $account->reseller_terms_version,
                'terms_accepted_at' => $account->reseller_terms_accepted_at,
            ]);
        });

        return back()->with('status', 'Status reseller berhasil diperbarui.');
    }
}
