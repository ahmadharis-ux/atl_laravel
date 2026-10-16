<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Atlantic Pedia Tab')</title>
<link rel="icon" href="{{ asset('assets/images/6.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/6.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/6.png') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/images/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('assets/images/site.webmanifest') }}">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="{{ asset('assets/css/atlantic.css') }}?v={{ filemtime(public_path('assets/css/atlantic.css')) }}" rel="stylesheet">
<style>
body{min-height:100vh}
.auth{min-height:100vh;min-height:100dvh}
.brand-panel{background:linear-gradient(150deg,var(--teal) -20%,var(--navy) 45%,var(--deep));color:#fff;position:relative;overflow:hidden;padding:3rem}
.brand-panel:before{content:"";position:absolute;right:-6rem;bottom:-6rem;width:26rem;height:26rem;border-radius:50%;background:rgba(100,253,185,.14);filter:blur(48px)}
.brand-panel>*{position:relative}
.logo{width:2.5rem;height:2.5rem;border-radius:.75rem;background:var(--tealc);color:var(--teal);display:flex;align-items:center;justify-content:center}
.feat{display:flex;gap:.75rem;align-items:flex-start}
.feat .ms{color:var(--mint);font-size:20px;margin-top:2px}
.form-wrap{width:100%;max-width:26rem}
.form-card{background:#fff;border-radius:1.5rem;padding:1.5rem;box-shadow:0 10px 30px -8px rgba(6,40,58,.15)}
@media(min-width:576px){.form-card{padding:2rem}}
@media(min-width:992px){.form-card{box-shadow:none;background:transparent;padding:0}}
.field{margin-bottom:1rem}
.field label{font-weight:700;font-size:.8125rem;margin-bottom:.375rem;display:block}
.inp{position:relative}
.inp>.ms:first-child{position:absolute;left:.875rem;top:50%;transform:translateY(-50%);font-size:20px;color:var(--mute);pointer-events:none}
.inp input{width:100%;height:3rem;padding:0 2.75rem 0 2.75rem;border:1px solid #d3e3e1;border-radius:.875rem;background:#f6fbfa;font-size:.875rem;color:var(--ink);transition:border-color .15s,box-shadow .15s}
.inp input::placeholder{color:#8a9a9c}
.inp input:focus{outline:0;border-color:var(--mintt);background:#fff;box-shadow:0 0 0 3px rgba(0,160,108,.15)}
.inp input.bad{border-color:var(--err);box-shadow:0 0 0 3px rgba(186,26,26,.1)}
.toggle{position:absolute;right:.5rem;top:50%;transform:translateY(-50%);border:0;background:transparent;color:var(--mute);width:2.25rem;height:2.25rem;border-radius:.5rem;display:flex;align-items:center;justify-content:center}
.toggle:hover{background:rgba(0,0,0,.05)}
.err{color:var(--err);font-size:.75rem;margin-top:.3rem;display:none}
.err.show{display:block}
.btn-main{width:100%;height:3.1rem;border:0;border-radius:.875rem;background:var(--navy);color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:transform .15s,background .15s}
.btn-main:hover{background:var(--deep)}.btn-main:active{transform:scale(.98)}
.btn-main .ms{color:var(--mint);font-size:20px}
.chk{display:flex;gap:.6rem;align-items:flex-start;font-size:.8125rem;color:var(--mute)}
.chk input{width:1.1rem;height:1.1rem;margin-top:.1rem;accent-color:var(--mintt);flex-shrink:0}
.lnk{color:var(--teal);font-weight:700}.lnk:hover{text-decoration:underline}
.divider{display:flex;align-items:center;gap:.75rem;color:var(--mute);font-size:.75rem;margin:1.25rem 0}
.divider:before,.divider:after{content:"";flex:1;height:1px;background:#d3e3e1}
.btn-alt{width:100%;height:3rem;border-radius:.875rem;border:1px solid #cfe0de;background:#fff;font-weight:600;display:flex;align-items:center;justify-content:center;gap:.5rem;color:var(--ink)}
.btn-alt:hover{border-color:rgba(0,160,108,.45)}
.alert-ap{border-radius:.875rem;padding:.75rem 1rem;font-size:.8125rem;font-weight:600}
.alert-ok{background:rgba(100,253,185,.25);color:var(--mintd)}
.alert-err{background:var(--errc);color:var(--err)}
.code-inp{width:100%;height:3.25rem;text-align:center;letter-spacing:.35em;font-family:Manrope,sans-serif;font-size:1.375rem;font-weight:800;border:1px solid #d3e3e1;border-radius:.875rem;background:#f6fbfa}
.code-inp:focus{outline:0;border-color:var(--mintt);background:#fff;box-shadow:0 0 0 3px rgba(0,160,108,.15)}
</style>
@stack('styles')
</head>
<body>
<div class="row g-0 auth">
  <aside class="col-lg-6 d-none d-lg-flex brand-panel flex-column justify-content-between">
    <div class="d-flex align-items-center gap-2"><div class="logo"><span class="ms">bolt</span></div><div class="d-flex flex-column"><b class="lh-1">Atlantic</b><span class="fs-11 fw-bold tx-mint text-uppercase mt-1" style="letter-spacing:.1em">Pedia Tab</span></div></div>
    <div style="max-width:28rem">
      <h2 class="fw-bold mb-3" style="font-size:2.25rem;line-height:1.2;letter-spacing:-.02em">Kelola semua transaksi PPOB dari satu tempat.</h2>
      <div class="d-flex flex-column gap-3">
        <div class="feat"><span class="ms">bolt</span><div>Kelola transaksi PPOB cepat, aman, dan otomatis 24 jam.</div></div>
        <div class="feat"><span class="ms">shield</span><div>Dilindungi verifikasi email dan autentikasi dua faktor.</div></div>
        <div class="feat"><span class="ms">devices</span><div>Pantau perangkat yang mengakses akun Anda kapan saja.</div></div>
      </div>
    </div>
  </aside>
  <main class="col-lg-6 d-flex align-items-center justify-content-center p-3 p-sm-4" style="padding-top:env(safe-area-inset-top,0px)">
    <div class="form-wrap py-4">
      <div class="d-flex d-lg-none align-items-center justify-content-center gap-2 mb-4">
        <div class="logo"><span class="ms">bolt</span></div>
        <div class="d-flex flex-column"><b class="lh-1">Atlantic</b><span class="fs-11 fw-bold tx-teal text-uppercase mt-1" style="letter-spacing:.1em">Pedia Tab</span></div>
      </div>
      <div class="form-card">
        @yield('content')
      </div>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click',function(e){var b=e.target.closest('[data-toggle]');if(b){var i=document.getElementById(b.getAttribute('data-toggle'));if(i){var t=i.type==='password';i.type=t?'text':'password';var s=b.querySelector('.ms');if(s)s.textContent=t?'visibility_off':'visibility'}}});
</script>
@stack('scripts')
</body>
</html>
