<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
                'latest_level' => $latest !== null ? data_get($latest->data, 'level', 'info') : null,
                'latest_unread' => $latest !== null && $latest->read_at === null,
                'html' => view('partials.notifications-list', [
                    'notifications' => $notifications,
                ])->render(),
            ]);
        }

        return view('notifications.index', [
            'notifications' => $user->unreadNotifications()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20),
            'unread' => $unread,
        ]);
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
