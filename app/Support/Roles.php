<?php

namespace App\Support;

final class Roles
{
    public const SUPERADMIN = 'superadmin';

    public const ADMIN = 'admin';

    public const STAFF = 'staff';

    public const CS = 'cs';

    public const USER = 'user';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [self::SUPERADMIN, self::ADMIN, self::STAFF, self::CS, self::USER];
    }

    /**
     * Roles that may access the admin area.
     *
     * @return array<int, string>
     */
    public static function adminArea(): array
    {
        return [self::SUPERADMIN, self::ADMIN, self::STAFF, self::CS];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::SUPERADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::STAFF => 'Staff',
            self::CS => 'Customer Service',
            self::USER => 'Pengguna',
        ];
    }

    public static function label(string $role): string
    {
        return self::labels()[$role] ?? ucfirst($role);
    }

    /**
     * Higher value means more authority.
     */
    public static function level(string $role): int
    {
        return [
            self::SUPERADMIN => 100,
            self::ADMIN => 80,
            self::STAFF => 60,
            self::CS => 40,
            self::USER => 10,
        ][$role] ?? 0;
    }

    public static function tone(string $role): string
    {
        return [
            self::SUPERADMIN => 'bg-errc-l tx-err',
            self::ADMIN => 'bg-tealc-m tx-tealt',
            self::STAFF => 'bg-sky-l tx-navy',
            self::CS => 'bg-gold tx-gold',
            self::USER => 'bg-mint-l tx-mintt',
        ][$role] ?? 'bg-tealc-m tx-tealt';
    }
}
