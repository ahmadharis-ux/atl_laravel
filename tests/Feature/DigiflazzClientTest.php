<?php

namespace Tests\Feature;

use App\Support\Digiflazz;
use App\Support\DigiflazzException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DigiflazzClientTest extends TestCase
{
    public function test_sign_is_md5_of_username_api_key_and_suffix(): void
    {
        $client = new Digiflazz('user', 'key');

        $this->assertSame(md5('user'.'key'.'depo'), $client->sign('depo'));
    }

    public function test_check_balance_posts_the_expected_payload(): void
    {
        Http::fake([
            'api.digiflazz.com/*' => Http::response(['data' => ['deposit' => 50000]]),
        ]);

        $result = (new Digiflazz('user', 'key'))->checkBalance();

        $this->assertSame(['deposit' => 50000], $result);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.digiflazz.com/v1/cek-saldo'
            && $request['username'] === 'user'
            && $request['cmd'] === 'deposit'
            && $request['sign'] === md5('user'.'key'.'depo'));
    }

    public function test_topup_signs_with_the_reference_id_and_omits_testing_by_default(): void
    {
        Http::fake([
            'api.digiflazz.com/*' => Http::response(['data' => ['rc' => '00', 'status' => 'Sukses']]),
        ]);

        (new Digiflazz('user', 'key'))->topup('ML-86', '183920144', 'TRX-1');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.digiflazz.com/v1/transaction'
            && $request['buyer_sku_code'] === 'ML-86'
            && $request['customer_no'] === '183920144'
            && $request['sign'] === md5('user'.'key'.'TRX-1')
            && ! isset($request['testing']));
    }

    public function test_topup_sends_testing_flag_when_requested(): void
    {
        Http::fake([
            'api.digiflazz.com/*' => Http::response(['data' => ['rc' => '00']]),
        ]);

        (new Digiflazz('user', 'key'))->topup('xld10', '087800001230', 'test1', testing: true);

        Http::assertSent(fn ($request): bool => $request['testing'] === true);
    }

    public function test_price_list_throws_when_digiflazz_returns_an_error(): void
    {
        Http::fake([
            'api.digiflazz.com/*' => Http::response(['data' => ['rc' => '83', 'message' => 'limit']]),
        ]);

        $this->expectException(DigiflazzException::class);
        $this->expectExceptionMessage('Digiflazz error [83]: limit');

        (new Digiflazz('user', 'key'))->priceList();
    }

    public function test_verify_webhook_checks_the_x_hub_signature(): void
    {
        $payload = '{"data":{"rc":"00"}}';
        $signature = 'sha1='.hash_hmac('sha1', $payload, 'secret');

        $client = new Digiflazz('user', 'key');

        $this->assertTrue($client->verifyWebhook($payload, $signature, 'secret'));
        $this->assertFalse($client->verifyWebhook($payload, 'sha1=wrong', 'secret'));
        $this->assertFalse($client->verifyWebhook($payload, null, 'secret'));
    }
}
