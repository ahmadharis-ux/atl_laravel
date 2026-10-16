<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use App\Support\TransactionLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Penerima webhook Midtrans berisi status akhir pembayaran QRIS.
 *
 * Endpoint ini tidak memakai sesi/CSRF karena dipanggil server Midtrans;
 * keaslian permintaan divalidasi lewat signature_key.
 */
class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, MidtransService $midtrans): JsonResponse
    {
        try {
            $midtrans->handleNotification($request->all());
        } catch (Throwable $e) {
            TransactionLogger::error('deposit.failed', 'Gagal memproses notifikasi Midtrans.', $e, [
                'order_id' => $request->input('order_id'),
            ]);
        }

        return response()->json(['message' => 'OK']);
    }
}
