<?php

namespace Tests\Feature;

use App\Support\GameNickname;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GameNicknameTest extends TestCase
{
    public function test_it_returns_the_nickname_for_a_supported_brand(): void
    {
        Http::fake([
            'api.isan.eu.org/nickname/ff*' => Http::response([
                'success' => true,
                'game' => 'Garena Free Fire',
                'id' => 9117778980,
                'name' => 'KBL-IKyyy√',
            ]),
        ]);

        $result = GameNickname::make()->check('free-fire', '9117778980');

        $this->assertSame(['status' => true, 'nickname' => 'KBL-IKyyy√'], $result);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.isan.eu.org/nickname/ff?id=9117778980');
    }

    public function test_it_sends_the_zone_as_server_for_mobile_legends(): void
    {
        Http::fake([
            'api.isan.eu.org/nickname/ml*' => Http::response([
                'success' => true,
                'name' => 'Outrageous Dominance',
            ]),
        ]);

        $result = GameNickname::make()->check('mobile-legends', '1114917746', '13486');

        $this->assertSame(['status' => true, 'nickname' => 'Outrageous Dominance'], $result);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.isan.eu.org/nickname/ml?id=1114917746&server=13486');
    }

    public function test_mobile_legends_requires_a_zone_without_calling_the_api(): void
    {
        Http::fake();

        $result = GameNickname::make()->check('mobile-legends', '1114917746');

        $this->assertSame(['status' => false, 'message' => 'Zone ID wajib diisi.'], $result);

        Http::assertNothingSent();
    }

    public function test_it_reports_when_the_account_is_not_found(): void
    {
        Http::fake([
            'api.isan.eu.org/nickname/ff*' => Http::response([
                'success' => true,
                'game' => 'Garena Free Fire',
                'id' => 1,
            ]),
        ]);

        $result = GameNickname::make()->check('free-fire', '1');

        $this->assertSame(['status' => false, 'message' => 'Nama akun tidak ditemukan.'], $result);
    }

    public function test_unsupported_brands_do_not_call_the_api(): void
    {
        Http::fake();

        $result = GameNickname::make()->check('telkomsel', '123456');

        $this->assertSame(['status' => false, 'message' => 'Produk ini tidak memiliki validasi.'], $result);

        Http::assertNothingSent();
    }
}
