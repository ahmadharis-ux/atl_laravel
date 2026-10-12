<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disk penyimpanan logo
    |--------------------------------------------------------------------------
    |
    | Saat ini disk `brands` menulis langsung ke folder public/brands. Untuk
    | pindah ke symlink storage, ubah disk `brands` di config/filesystems.php
    | lalu jalankan `php artisan storage:link`.
    |
    */
    'disk' => 'brands',

    /*
    |--------------------------------------------------------------------------
    | Sumber otomatis
    |--------------------------------------------------------------------------
    |
    | 1. `resolve` dipakai untuk menebak domain resmi dari nama brand.
    | 2. `logo` dipakai untuk mengunduh gambar berdasarkan domain tersebut.
    | `{domain}` akan diganti dengan domain hasil langkah pertama.
    |
    */
    'resolve_endpoint' => 'https://autocomplete.clearbit.com/v1/companies/suggest',

    /*
     | Sumber gambar dicoba berurutan sampai ada yang menghasilkan gambar valid.
     | `?fallback=false` membuat unavatar mengembalikan 404 (bukan ikon generik)
     | saat logo tidak ditemukan, sehingga bisa dilewati.
     */
    'logo_endpoints' => [
        'https://unavatar.io/{domain}?fallback=false',
        'https://t3.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=https://{domain}&size=256',
    ],

    'timeout' => 20,
    'min_bytes' => 200,
    'score_threshold' => 60,

    /*
    |--------------------------------------------------------------------------
    | Peta domain kurasi (menang atas hasil otomatis)
    |--------------------------------------------------------------------------
    |
    | Kunci = slug brand. Isi hanya brand yang domainnya kita yakini agar logo
    | yang tersimpan benar. Brand lain dicoba otomatis dari namanya.
    |
    */
    'overrides' => [
        // Operator seluler & data
        'axis' => 'axis.co.id',
        'byu' => 'byu.id',
        'indosat' => 'indosat.com',
        'tri' => 'tri.co.id',
        'xl' => 'xl.co.id',
        'telkomsel' => 'telkomsel.com',
        'smartfren' => 'smartfren.com',
        'ais' => 'ais.co.th',
        'dtac' => 'dtac.co.th',
        'truemove-h' => 'truemoveh.co.th',
        'celcom' => 'celcom.com.my',
        'digi' => 'digi.com.my',
        'maxis' => 'maxis.com.my',
        'hotlink' => 'hotlink.com.my',
        'tunetalk' => 'tunetalk.com',
        'umobile' => 'u.com.my',
        'xox' => 'xox.com.my',
        'yes' => 'yes.my',
        'm1' => 'm1.com.sg',
        'globe' => 'globe.com.ph',
        'smart-communications' => 'smart.com.ph',

        // Dompet digital & pembayaran
        'dana' => 'dana.id',
        'go-pay' => 'gopay.co.id',
        'linkaja' => 'linkaja.id',
        'ovo' => 'ovo.id',
        'shopee-pay' => 'shopee.co.id',
        'grab' => 'grab.com',
        'touch-n-go' => 'tngdigital.com.my',

        // Voucher & retail
        'alfamart-voucher' => 'alfamart.co.id',
        'indomaret' => 'indomaret.co.id',
        'google-play' => 'google.com',
        'google-play-indonesia' => 'google.com',
        'google-play-us-region' => 'google.com',
        'itunes' => 'apple.com',
        'xbox' => 'xbox.com',
        'playstation' => 'playstation.com',
        'minecraft' => 'minecraft.net',
        'razer-gold' => 'razer.com',
        'unipin-voucher' => 'unipin.com',
        'tokopedia' => 'tokopedia.com',
        'traveloka-e-voucher' => 'traveloka.com',
        'kopi-kenangan' => 'kopikenangan.com',
        'megaxus' => 'megaxus.com',

        // Tagihan & TV
        'pln' => 'pln.co.id',
        'pertamina-gas' => 'pertamina.com',
        'transvision' => 'transvision.co.id',
        'vision' => 'visionplus.id',
        'genflix' => 'genflix.co.id',
        'viu' => 'viu.com',
        'vidio' => 'vidio.com',
        'wetv' => 'wetv.vip',
        'matrix-garuda-sinema-mola-matrix' => 'mola.tv',

        // Game (trafik tinggi / domain jelas)
        'mobile-legends' => 'mobilelegends.com',
        'free-fire' => 'freefiremobile.com',
        'free-fire-max' => 'freefiremobile.com',
        'pubg-mobile' => 'pubgmobile.com',
        'pubg-mobile-lite' => 'pubgmobile.com',
        'pubg-new-state-mobile' => 'pubgnewstate.com',
        'call-of-duty-mobile' => 'callofduty.com',
        'valorant' => 'playvalorant.com',
        'league-of-legends-pc' => 'leagueoflegends.com',
        'league-of-legends-wild-rift' => 'wildrift.leagueoflegends.com',
        'pokemon-unite' => 'unite.pokemon.com',
        'dragon-city' => 'dragoncitygame.com',
        'lords-mobile' => 'igg.com',
        'growtopia' => 'growtopiagame.com',
        'asphalt-9' => 'gameloft.com',
        'tower-of-fantasy' => 'toweroffantasy.com',
        'marvel-rivals' => 'marvelrivals.com',
        'wuthering-waves' => 'kurogames.com',
    ],

];
