<?php

namespace App\Support;

use RuntimeException;

/**
 * Error yang dikembalikan API Digiflazz (mis. signature salah, limit, dst).
 */
final class DigiflazzException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $response
     */
    public static function fromResponse(array $response): self
    {
        $rc = (string) ($response['rc'] ?? '');
        $message = (string) ($response['message'] ?? 'Respons Digiflazz tidak dikenali.');

        return new self(trim("Digiflazz error [{$rc}]: {$message}"));
    }
}
