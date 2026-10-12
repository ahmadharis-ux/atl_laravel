<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Digiflazz;
use App\Support\OrderFulfillment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Penerima webhook Digiflazz berisi status akhir transaksi.
 *
 * Endpoint ini tidak memakai sesi/CSRF karena dipanggil server Digiflazz;
 * keaslian permintaan divalidasi lewat signature X-Hub-Signature.
 */
class DigiflazzWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();

        if (! Digiflazz::make()->verifyWebhook($payload, $request->header('X-Hub-Signature'))) {
            return response('Unauthorized', 401);
        }

        $decoded = json_decode($payload, true);
        $items = $decoded['data'] ?? [];

        if (isset($items['ref_id'])) {
            $items = [$items];
        }

        if (! is_array($items)) {
            return response('OK', 200);
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $refId = $item['ref_id'] ?? null;

            if ($refId === null) {
                continue;
            }

            $order = Order::query()->where('provider_ref', $refId)->first();

            if ($order !== null) {
                OrderFulfillment::applyResult($order, $item);
            }
        }

        return response('OK', 200);
    }
}
