<?php

namespace App\Support;

use App\Models\PinAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PinAuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function log(User $user, string $action, ?Request $request = null, array $metadata = []): PinAuditLog
    {
        return PinAuditLog::query()->create([
            'user_id' => $user->getKey(),
            'action' => $action,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 500, '') : null,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
