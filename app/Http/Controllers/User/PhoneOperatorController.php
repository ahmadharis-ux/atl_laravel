<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Support\PhoneOperator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhoneOperatorController extends Controller
{
    /**
     * Deteksi operator dari nomor HP untuk menampilkan paket pulsa/data.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:20'],
        ]);

        $detected = PhoneOperator::detect($validated['number']);

        if ($detected === null) {
            return response()->json([
                'status' => false,
                'message' => 'Operator tidak dikenali. Pastikan nomor HP sudah benar.',
            ]);
        }

        $brand = Brand::query()->where('slug', $detected['brand_slug'])->first();

        if ($brand === null) {
            return response()->json([
                'status' => false,
                'message' => 'Operator belum tersedia saat ini.',
            ]);
        }

        return response()->json([
            'status' => true,
            'operator' => $brand->name,
            'brand_id' => $brand->getKey(),
            'brand_slug' => $brand->slug,
            'brand_name' => $brand->name,
            'brand_logo' => $brand->logoUrl(),
        ]);
    }
}
