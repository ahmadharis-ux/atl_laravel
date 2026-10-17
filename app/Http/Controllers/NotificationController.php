<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pusat notifikasi user (ikon lonceng di topbar).
 *
 * - index: daftar notifikasi; balasan JSON saat dipanggil AJAX (dropdown),
 *   halaman penuh saat dibuka langsung.
 * - open: tandai satu notifikasi dibaca lalu arahkan ke tautan tujuannya.
 * - readAll: tandai semua notifikasi dibaca.
 */
class NotificationController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): View|Response
    {
        $user = $request->user();
        $unread = $user->unreadNotifications()->count();

        if ($request->ajax() || $request->expectsJson()) {
            $notifications = $user->unreadNotifications()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(15)
                ->get();
            $latest = $notifications->first();

            return response()->json([
                'unread' => $unread,
                'latest_id' => $latest?->id,
                'latest_title' => $latest !== null ? data_get($latest->data, 'title') : null,
                'latest_body' => $latest !== null ? data_get($latest->data, 'body') : null,
                'latest_level' => $latest !== null ? data_get($latest->data, 'level', 'info') : null,
                'latest_unread' => $latest !== null && $latest->read_at === null,
                'html' => view('partials.notifications-list', [
                    'notifications' => $notifications,
                    'reorderProducts' => $this->reorderProducts($notifications),
                ])->render(),
            ]);
        }

        $filter = $this->resolveFilter($request);
        $search = $this->resolveSearch($request);

        $notifications = $this->notificationsQuery($user, $filter, $search)
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unread' => $unread,
            'filter' => $filter,
            'search' => $search,
            'total' => $user->notifications()->count(),
            'readCount' => $user->notifications()->whereNotNull('read_at')->count(),
            'reorderProducts' => $this->reorderProducts($notifications->getCollection()),
            'emptyMessage' => $this->emptyMessage($filter, $search),
        ]);
    }

    /**
     * Muat lebih banyak (AJAX): kembalikan baris berikutnya beserta footer
     * tombol "Muat lebih banyak" untuk halaman notifikasi.
     */
    public function list(Request $request): JsonResponse
    {
        $user = $request->user();
        $filter = $this->resolveFilter($request);
        $search = $this->resolveSearch($request);

        $notifications = $this->notificationsQuery($user, $filter, $search)
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return response()->json([
            'rows' => view('partials.notifications-list', [
                'notifications' => $notifications,
                'reorderProducts' => $this->reorderProducts($notifications->getCollection()),
                'emptyMessage' => $this->emptyMessage($filter, $search),
            ])->render(),
            'footer' => view('partials.notifications-footer', ['notifications' => $notifications])->render(),
        ]);
    }

    /**
     * Notifikasi milik user dengan filter tab + pencarian, siap dipaginasi.
     */
    private function notificationsQuery(User $user, string $filter, string $search): MorphMany
    {
        return $user->notifications()
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($filter === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $query->where(fn ($q) => $q->where('data', 'like', $term));
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function resolveFilter(Request $request): string
    {
        $filter = (string) $request->query('filter', 'all');

        return in_array($filter, ['all', 'unread', 'read'], true) ? $filter : 'all';
    }

    private function resolveSearch(Request $request): string
    {
        return trim((string) $request->query('q', ''));
    }

    private function emptyMessage(string $filter, string $search): string
    {
        if ($search !== '') {
            return 'Tidak ada notifikasi yang cocok dengan pencarian.';
        }

        return match ($filter) {
            'unread' => 'Tidak ada notifikasi yang belum dibaca.',
            'read' => 'Belum ada notifikasi yang sudah dibaca.',
            default => 'Belum ada notifikasi.',
        };
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);

        if ($item->read_at === null) {
            $item->markAsRead();
        }

        $url = data_get($item->data, 'url');

        return is_string($url) && $url !== ''
            ? redirect()->to($this->sameHostTarget($url))
            : redirect()->route('notifications.index');
    }

    /**
     * Peta status produk yang direferensikan notifikasi pesanan, agar tombol
     * "Beli Lagi" hanya tampil saat produk masih dapat dibeli.
     *
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return array<int|string, bool>
     */
    private function reorderProducts($notifications): array
    {
        $ids = $notifications
            ->map(fn ($notification) => data_get($notification->data, 'context.type') === 'order'
                ? data_get($notification->data, 'context.product_id')
                : null)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->pluck('status', 'id')
            ->map(fn ($status) => (bool) $status)
            ->all();
    }

    /**
     * Ubah tautan notifikasi menjadi path relatif pada host saat ini.
     *
     * Tautan disimpan sebagai URL absolut (dipakai juga oleh push FCM), tetapi
     * redirect ke host yang tersimpan bisa memutus sesi bila aplikasi dibuka
     * lewat host berbeda (mis. notifikasi dibuat di `localhost`, dibuka di
     * `127.0.0.1`). Karena itu host dibuang dan hanya path + query yang dipakai.
     */
    private function sameHostTarget(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return '/';
        }

        $query = parse_url($url, PHP_URL_QUERY);

        return is_string($query) && $query !== '' ? $path.'?'.$query : $path;
    }

    public function readAll(Request $request): Response|RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->ajax()) {
            return response()->json(['unread' => 0]);
        }

        return redirect()->back();
    }
}
