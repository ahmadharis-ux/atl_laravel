<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Akses Ditolak - Atlantic Pedia Tab</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/atlantic.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/components.css') }}" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/images/6.png') }}">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-9 col-md-7 col-lg-5">
                <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                    <div class="ibox ibox-48 bg-deep tx-mint"><span class="ms">shield_person</span></div>
                    <div class="d-flex flex-column text-start">
                        <span class="fw-bold lh-1 tx-navy">Atlantic</span>
                        <span class="fs-11 fw-bold tx-teal text-uppercase mt-1" style="letter-spacing:.1em">Pedia Tab</span>
                    </div>
                </div>

                <div class="card-ap r-card p-4 p-sm-5">
                    <x-ui.empty-state
                        icon="block"
                        tone="bg-errc-l tx-err"
                        title="403 — Akses ditolak"
                        description="Anda tidak memiliki izin untuk membuka halaman ini."
                    >
                        <a href="{{ route('dashboard') }}" class="btn-ap btn-navy">
                            <span class="ms">arrow_back</span>Kembali ke Beranda
                        </a>
                    </x-ui.empty-state>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
