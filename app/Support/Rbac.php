<?php

namespace App\Support;

use App\Models\User;

final class Rbac
{
    /**
     * Permission ditata per modul untuk ditampilkan di UI.
     *
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return [
            'Dashboard' => [
                'dashboard.view' => 'Melihat dashboard admin',
            ],
            'Landing Page' => [
                'landing.view' => 'Melihat konten landing page',
                'landing.update' => 'Mengubah konten landing page',
            ],
            'Tiket & Aduan' => [
                'tickets.view' => 'Melihat tiket & aduan',
                'tickets.update' => 'Membalas / memproses tiket',
            ],
            'Pengguna' => [
                'users.view' => 'Melihat daftar pengguna',
                'users.create' => 'Menambah pengguna',
                'users.update' => 'Mengubah pengguna',
                'users.delete' => 'Menghapus pengguna',
                'users.assign-role' => 'Menetapkan peran pengguna',
            ],
            'Manajemen Halaman' => [
                'features.view' => 'Melihat pengaturan halaman pengguna',
                'features.update' => 'Mengaktifkan / menonaktifkan halaman pengguna',
            ],
            'Role & Permission' => [
                'roles.view' => 'Melihat daftar peran',
                'roles.create' => 'Menambah peran',
                'roles.update' => 'Mengubah peran & izin',
                'roles.delete' => 'Menghapus peran',
            ],
            'Customer Topup' => [
                'topups.view' => 'Melihat daftar topup',
                'topups.update' => 'Memproses / mengubah topup',
            ],
            'Master Bank' => [
                'master-banks.view' => 'Melihat master bank',
                'master-banks.update' => 'Menambah / mengubah master bank',
            ],
            'Banner Login' => [
                'login-banners.view' => 'Melihat banner login',
                'login-banners.update' => 'Menambah / mengubah banner login',
            ],
            'Banner Dashboard' => [
                'dashboard-banners.view' => 'Melihat banner dashboard',
                'dashboard-banners.update' => 'Menambah / mengubah banner dashboard',
            ],
            'Master Brand' => [
                'master-brands.view' => 'Melihat master brand',
                'master-brands.update' => 'Mengubah logo master brand',
            ],
            'Transaksi' => [
                'transactions.view' => 'Melihat daftar transaksi',
                'transactions.update' => 'Memproses / mengubah status transaksi',
            ],
            'Produk' => [
                'products.view' => 'Melihat daftar produk',
                'products.update' => 'Mengubah produk',
            ],
            'Merchandise' => [
                'merchandise.view' => 'Melihat daftar merchandise',
                'merchandise.update' => 'Menambah / mengubah merchandise',
            ],
            'Penukaran Poin' => [
                'redemptions.view' => 'Melihat daftar penukaran poin',
                'redemptions.update' => 'Memproses penukaran poin',
            ],
            'Pengaturan Loyalitas' => [
                'loyalty.view' => 'Melihat pengaturan tier loyalitas',
                'loyalty.update' => 'Mengubah threshold tier loyalitas',
            ],
            'Reseller' => [
                'resellers.view' => 'Melihat laporan reseller',
            ],
            'Laporan' => [
                'reports.view' => 'Melihat laporan & analitik',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        $names = [];
        foreach (self::groups() as $permissions) {
            foreach (array_keys($permissions) as $permission) {
                $names[] = $permission;
            }
        }

        return $names;
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::groups() as $permissions) {
            $labels += $permissions;
        }

        return $labels;
    }

    public static function label(string $permission): string
    {
        return self::labels()[$permission] ?? $permission;
    }

    /**
     * Izin default untuk tiap role.
     *
     * @return array<string, array<int, string>>
     */
    public static function defaults(): array
    {
        return [
            Roles::SUPERADMIN => self::all(),
            Roles::ADMIN => [
                'dashboard.view',
                'landing.view', 'landing.update',
                'tickets.view', 'tickets.update',
                'users.view', 'users.create', 'users.update', 'users.delete', 'users.assign-role',
                'roles.view', 'roles.create', 'roles.update',
                'features.view', 'features.update',
                'topups.view', 'topups.update', 'transactions.view', 'transactions.update',
                'products.view', 'products.update',
                'master-banks.view', 'master-banks.update',
                'master-brands.view', 'master-brands.update',
                'login-banners.view', 'login-banners.update',
                'dashboard-banners.view', 'dashboard-banners.update',
                'merchandise.view', 'merchandise.update',
                'redemptions.view', 'redemptions.update',
                'loyalty.view', 'loyalty.update',
                'resellers.view',
                'reports.view',
            ],
            Roles::STAFF => [
                'dashboard.view',
                'landing.view', 'landing.update',
                'tickets.view', 'tickets.update',
                'users.view', 'users.create', 'users.update', 'users.assign-role',
                'roles.view',
                'topups.view', 'topups.update', 'transactions.view', 'transactions.update',
                'products.view',
                'master-banks.view', 'master-banks.update',
                'master-brands.view', 'master-brands.update',
                'login-banners.view', 'login-banners.update',
                'dashboard-banners.view', 'dashboard-banners.update',
                'merchandise.view', 'merchandise.update',
                'redemptions.view', 'redemptions.update',
                'loyalty.view', 'loyalty.update',
                'resellers.view',
                'reports.view',
            ],
            Roles::CS => [
                'dashboard.view',
                'tickets.view', 'tickets.update',
                'users.view',
                'topups.view', 'topups.update', 'transactions.view', 'transactions.update',
                'products.view',
                'master-banks.view',
                'master-brands.view',
                'login-banners.view',
                'dashboard-banners.view',
                'merchandise.view',
                'redemptions.view',
                'loyalty.view',
                'resellers.view',
                'reports.view',
            ],
            Roles::USER => [],
        ];
    }

    /**
     * Role yang boleh ditetapkan oleh $actor.
     *
     * @return array<int, string>
     */
    public static function assignableRoles(User $actor): array
    {
        if ($actor->hasRole(Roles::SUPERADMIN)) {
            return Roles::all();
        }

        $level = self::actorLevel($actor);

        return array_values(array_filter(
            Roles::all(),
            fn (string $role) => self::level($role) < $level,
        ));
    }

    public static function canAssign(User $actor, string $role): bool
    {
        return in_array($role, self::assignableRoles($actor), true);
    }

    /**
     * Boleh mengelola akun target (mengubah/menghapus).
     */
    public static function canManageUser(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return true;
        }

        if ($actor->hasRole(Roles::SUPERADMIN)) {
            return true;
        }

        return self::actorLevel($target) < self::actorLevel($actor);
    }

    public static function actorLevel(User $user): int
    {
        return $user->getRoleNames()
            ->map(fn (string $role) => Roles::level($role))
            ->max() ?? 0;
    }

    public static function level(string $role): int
    {
        return Roles::level($role);
    }
}
