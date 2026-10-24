<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\Request;

trait HasPerPage
{
    protected function perPage(Request $request, int $default = 10): int
    {
        $allowed = [10, 50, 100, 150, 200, 250];
        $value = (int) $request->query('per_page', $default);

        if (! in_array($value, $allowed, true)) {
            $value = $default;
        }

        return $value;
    }
}
