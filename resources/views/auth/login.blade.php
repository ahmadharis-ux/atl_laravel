<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Masuk - Atlantic Pedia</title>
<link rel="icon" href="{{ asset('assets/images/logo.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/logo.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/logo.png') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/images/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('assets/images/site.webmanifest') }}">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<style>/* Atlantic Pedia Tab - custom styles (di atas Bootstrap 5.3) */
:root{--bg:#f3fbfc;--ink:#151d1e;--mute:#42474c;--line:#e7eff0;--navy:#06283a;--deep:#00121e;--teal:#00696f;--tealc:#9ff0f7;--tealt:#0b6f76;--mint:#64fdb9;--mintd:#002113;--mintt:#00a06c;--sky:#c8e6fe;--skyd:#adcae2;--err:#ba1a1a;--errc:#ffdad6;--gold:#fff3d6;--goldt:#a76b00;--bs-body-font-family:"Plus Jakarta Sans",sans-serif;--bs-body-bg:#e4f2f0;--page:linear-gradient(180deg,#eaf6f4 0%,#dcefec 100%);--bs-body-color:#151d1e;--bs-body-font-size:.875rem}
html,body{overscroll-behavior:none;min-height:100%}
body{background:#e4f2f0 var(--page) fixed}
body{padding-top:env(safe-area-inset-top,0px);-webkit-font-smoothing:antialiased}
::-webkit-scrollbar{display:none}
a{text-decoration:none;color:inherit}
.ms{font-family:"Material Symbols Outlined";font-weight:400;font-style:normal;line-height:1;display:inline-block;font-size:22px;font-variation-settings:'FILL' 0}
.ms.f{font-variation-settings:'FILL' 1}.ms-sm{font-size:16px}.ms-md{font-size:18px}.ms-lg{font-size:30px}
.num{font-family:Manrope,sans-serif}
.fs-11{font-size:11px}.fs-12{font-size:12px}.fw-800{font-weight:800}
/* warna */
.bg-navy{background:var(--navy)}.bg-deep{background:var(--deep)}.bg-mint{background:var(--mint)}.bg-mintt{background:var(--mintt)}
.bg-tealc{background:var(--tealc)}.bg-tealc-l{background:rgba(159,240,247,.28)}.bg-tealc-m{background:rgba(159,240,247,.45)}
.bg-gold{background:var(--gold)}.bg-sky-l{background:rgba(200,230,254,.6)}.bg-mint-l{background:rgba(100,253,185,.25)}
.bg-errc-l{background:rgba(255,218,214,.55)}.bg-line{background:var(--line)}.bg-glass{background:rgba(255,255,255,.1)}
.tx-navy{color:var(--navy)}.tx-teal{color:var(--teal)}.tx-tealt{color:var(--tealt)}.tx-tealc{color:var(--tealc)}
.tx-mint{color:var(--mint)}.tx-mintd{color:var(--mintd)}.tx-mintt{color:var(--mintt)}.tx-skyd{color:var(--skyd)}
.tx-gold{color:var(--goldt)}.tx-err{color:var(--err)}.tx-mute{color:var(--mute)}.tx-deep{color:var(--deep)}
/* sidebar & header desktop */
.sidebar{position:fixed;left:0;top:0;bottom:0;width:14rem;background:var(--navy);z-index:1040;padding:1.25rem .75rem}
.side-link{display:flex;align-items:center;gap:.75rem;padding:.625rem .75rem;border-radius:.5rem;color:var(--skyd);font-weight:600;font-size:.875rem}
.side-link:hover{background:rgba(255,255,255,.1);color:#fff}
.side-link.active{background:var(--teal);color:#fff}
.topbar{position:fixed;top:0;left:14rem;right:0;height:4rem;background:rgba(228,242,240,.88);backdrop-filter:blur(16px);box-shadow:0 1px 8px rgba(0,0,0,.04);z-index:1030;padding:0 1.5rem}
.sq{width:2.5rem;height:2.5rem;border-radius:.5rem;background:#fff;border:0;display:flex;align-items:center;justify-content:center;position:relative}
.dot{position:absolute;width:8px;height:8px;border-radius:50%}
.main{padding:.5rem 1rem 8rem;max-width:1400px}
@media(min-width:576px){.main{padding-left:1.5rem;padding-right:1.5rem}}
@media(min-width:992px){.main{padding:5.5rem 1.5rem 2rem 15.5rem;max-width:none;margin:0}}
/* kartu */
.r-card{border-radius:1.5rem}.r-sm{border-radius:1rem}
@media(min-width:992px){.r-card{border-radius:.75rem}.r-sm{border-radius:.75rem}}
.card-ap{background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.balance{position:relative;overflow:hidden;background:linear-gradient(135deg,var(--navy),var(--deep));color:#fff;padding:1.25rem;box-shadow:0 10px 24px -4px rgba(6,40,58,.25)}
.balance:before,.balance:after{content:"";position:absolute;border-radius:50%;filter:blur(32px);pointer-events:none}
.balance:before{right:-3rem;top:-3rem;width:12rem;height:12rem;background:rgba(159,240,247,.1)}
.balance:after{left:-2.5rem;bottom:-2.5rem;width:11rem;height:11rem;background:rgba(100,253,185,.1)}
.balance>*{position:relative}
.saldo{font-size:2.25rem;font-weight:800;letter-spacing:-.02em;line-height:1}
@media(min-width:576px){.saldo{font-size:3rem}.balance{padding:1.5rem}}
.btn-ap{border:0;font-weight:700;font-size:.875rem;padding:.75rem 1rem;border-radius:1rem;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:transform .15s}
.btn-ap:active{transform:scale(.98)}
@media(min-width:992px){.btn-ap{border-radius:50rem}}
.btn-mint{background:var(--mint);color:var(--mintd)}
.btn-glass{background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.1)}
.pill{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:50rem;font-size:.75rem;font-weight:700;white-space:nowrap}
.pulse{width:6px;height:6px;border-radius:50%;background:var(--mint);animation:p 1.6s infinite}
@keyframes p{50%{opacity:.35}}
@media(prefers-reduced-motion:reduce){.pulse{animation:none}}
.ibox{display:flex;align-items:center;justify-content:center;flex-shrink:0;border-radius:1rem}
.ibox-48{width:3rem;height:3rem}.ibox-44{width:2.75rem;height:2.75rem;border-radius:.75rem}
@media(min-width:576px){.sc .ibox{width:3.5rem;height:3.5rem}}
.sc:hover{background:var(--bg)}
.mini-poin{background:var(--gold);border:1px solid rgba(251,191,36,.35)}
.mini-admin{background:rgba(159,240,247,.25);border:1px solid var(--tealc)}
/* promo */
.promo{position:relative;overflow:hidden;color:#fff;height:100%;min-height:210px;background:linear-gradient(135deg,var(--teal),var(--navy) 55%,var(--deep));box-shadow:0 4px 12px rgba(0,0,0,.12)}
@media(min-width:576px){.promo{min-height:260px}}@media(min-width:992px){.promo{min-height:340px}}
.promo .glow{position:absolute;right:-2.5rem;bottom:-4rem;width:16rem;height:16rem;border-radius:50%;background:rgba(100,253,185,.15);filter:blur(32px)}
.promo .bgico{position:absolute;right:1.25rem;top:3.5rem;font-size:110px;color:rgba(255,255,255,.1)}
.promo-in{position:relative;z-index:1;height:100%;min-height:inherit;padding:1.25rem}
@media(min-width:576px){.promo-in{padding:1.5rem}}
.btn-white{background:#fff;color:var(--deep);border:0;font-weight:700;padding:.65rem 1.25rem;border-radius:.75rem;box-shadow:0 6px 16px rgba(0,0,0,.2)}
.btn-white:hover{background:var(--tealc)}
@media(min-width:992px){.btn-white{border-radius:50rem}}
/* katalog */
.cat{display:flex;flex-direction:column;align-items:center;text-align:center;padding:.75rem;background:#fff;border-radius:1rem;box-shadow:0 1px 3px rgba(0,0,0,.06);transition:transform .15s,box-shadow .15s;height:100%}
.cat{border:1px solid transparent}
.cat:hover,.cat:focus-visible{border-color:rgba(0,160,108,.45);box-shadow:0 6px 16px rgba(0,0,0,.06);outline:0}
.cat-ic{background:rgba(100,253,185,.18);color:var(--mintt)}
.cat:active{transform:scale(.98)}
@media(min-width:576px){.cat{padding:1rem}}
/* transaksi */
.trx li{display:flex;align-items:center;gap:.75rem;padding:.75rem 0;border-bottom:1px solid var(--line)}
.trx li:last-child{border:0}
.trx .t{min-width:0;flex:1}
/* WA & info */
.wa{display:flex;align-items:center;gap:.75rem;padding:.875rem;border-radius:1rem;background:rgba(159,240,247,.25);border:1px solid var(--tealc)}
.wa .ic{width:3rem;height:3rem;border-radius:.75rem;background:var(--mintt);color:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.wa .ic .ms,.wa .sub{display:none}
.wa .go{background:var(--navy);color:#fff;font-weight:700;font-size:.875rem;padding:.65rem 1rem;border-radius:.75rem;flex-shrink:0}
.info{display:flex;align-items:center;gap:.75rem;padding:1rem;border-radius:1rem;background:var(--navy);color:#fff}
.info .ic{background:rgba(255,255,255,.1);color:var(--mint);width:2.75rem;height:2.75rem;border-radius:.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.info small{color:var(--tealc)}
@media(min-width:992px){
 .wa{flex-direction:column;align-items:flex-start;padding:1rem;border:0;border-radius:.75rem;background:linear-gradient(135deg,var(--deep),var(--navy));color:#fff}
 .wa .ic{background:rgba(100,253,185,.2);color:var(--mint)}.wa .ic span{display:none}.wa .ic .ms{display:inline-block}
 .wa small{color:var(--skyd)!important}.wa .go{background:var(--mint);color:var(--mintd);border-radius:50rem}
 .info{background:#fff;color:var(--ink);border-radius:.75rem;box-shadow:0 1px 3px rgba(0,0,0,.06)}
 .info .ic{background:rgba(159,240,247,.4);color:var(--tealt)}.info small{color:var(--mute)}
}
/* bottom nav */
.bnav{position:fixed;bottom:0;left:0;right:0;z-index:1050;background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border-top:1px solid var(--line);padding-bottom:env(safe-area-inset-bottom,0px)}
.bnav .grid5{display:grid;grid-template-columns:repeat(5,1fr);align-items:end;max-width:36rem;margin:0 auto;padding:.5rem .5rem .375rem;font-size:11px;font-weight:600;color:var(--mute)}
.bnav a{display:flex;flex-direction:column;align-items:center;gap:2px;padding:.25rem 0}
.bnav a.on{color:var(--teal)}
.fab{width:3.5rem;height:3.5rem;border-radius:50%;background:var(--navy);color:var(--mint);box-shadow:0 0 0 4px #fff,0 8px 18px rgba(0,0,0,.25);display:flex;align-items:center;justify-content:center}
.bnav a.mid{margin-top:-1.75rem;color:var(--ink)}
</style>
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
.btn-alt{width:100%;height:3rem;border-radius:.875rem;border:1px solid #cfe0de;background:#fff;font-weight:600;display:flex;align-items:center;justify-content:center;gap:.5rem}
.btn-alt:hover{border-color:rgba(0,160,108,.45)}
.strength{display:flex;gap:4px;margin-top:.5rem}.strength i{flex:1;height:4px;border-radius:4px;background:#d3e3e1;transition:background .2s}
.strength[data-s="1"] i:nth-child(1){background:var(--err)}
.strength[data-s="2"] i:nth-child(-n+2){background:#e0a100}
.strength[data-s="3"] i:nth-child(-n+3){background:var(--mintt)}
.strength[data-s="4"] i{background:var(--mintt)}
</style>
</head>
<body>
<div class="row g-0 auth">
  <!-- Panel brand (desktop) -->
  <aside class="col-lg-6 d-none d-lg-flex brand-panel flex-column justify-content-between">
    {{-- <img src="{{ asset('assets/images/logo_with_right_text.png') }}" alt="Atlantic Pedia Tab" style="height:2.75rem;"> --}}
    <div style="max-width:28rem">
      <img src="{{ asset('assets/images/logo_with_right_text.png') }}" alt="Atlantic Pedia Tab" style="width: 80%">
      <h2 class="fw-bold mb-3" style="font-size:2.25rem;line-height:1.2;letter-spacing:-.02em">Kelola semua transaksi PPOB dari satu tempat.</h2>
      <div class="d-flex flex-column gap-3 tx-skyd">
        <div class="feat"><span class="ms">check_circle</span><span>Pulsa, paket data, token PLN, voucher game, dan e-wallet dalam satu dashboard.</span></div>
        <div class="feat"><span class="ms">check_circle</span><span>Server PPOB stabil 24/7 dengan notifikasi gangguan real-time.</span></div>
        {{-- <div class="feat"><span class="ms">check_circle</span><span>Gratis biaya admin transfer dan poin reward di setiap transaksi.</span></div> --}}
      </div>
    </div>
    <span class="fs-12 tx-skyd">&copy; <span id="yr"></span> Atlantic Pedia</span>
  </aside>

  <!-- Form -->
  <main class="col-lg-6 d-flex align-items-center justify-content-center p-3 p-sm-4" style="padding-top:max(1rem,env(safe-area-inset-top))!important">
    <div class="form-wrap py-3">
      <div class="d-flex d-lg-none align-items-center justify-content-center gap-2 mb-4"><img src="{{ asset('assets/images/logo_with_right_text.png') }}" alt="Atlantic Pedia Tab" style="height:2.25rem;width:auto"></div>
      <div class="form-card">
        <h1 class="fw-bold mb-1" style="font-size:1.625rem;letter-spacing:-.015em">Selamat datang kembali</h1>
        <p class="tx-mute mb-4">Masuk untuk mengelola transaksi dan saldo Anda.</p>
@if (session('status'))
<div class="alert alert-success border-0 mb-3 fs-12">{{ session('status') }}</div>
@endif
<form action="{{ route('login') }}" method="POST" novalidate>
  @csrf
  <div class="field"><label for="email">Email</label><div class="inp"><span class="ms">person</span><input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="username" required autofocus @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif></div>
    @error('email')<div class="err show" id="email-error">{{ $message }}</div>@enderror</div>
  <div class="field"><label for="password">Kata sandi</label><div class="inp"><span class="ms">lock</span><input id="password" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif><button type="button" class="toggle" data-toggle="password" aria-label="Tampilkan atau sembunyikan kata sandi"><span class="ms">visibility</span></button></div>
    @error('password')<div class="err show" id="password-error">{{ $message }}</div>@enderror</div>
  <div class="d-flex align-items-center justify-content-between mb-4">
    <label class="chk m-0"><input type="checkbox" name="remember" value="1">Ingat saya</label>
    <a href="{{ route('password.request') }}" class="lnk fs-12">Lupa kata sandi?</a>
  </div>
  <button type="submit" class="btn-main">Masuk</button>
</form>
@if (config('services.google.client_id'))
<div class="divider">atau</div>
<a href="{{ route('auth.google.redirect') }}" class="btn-alt">@include('partials.google-logo')Masuk dengan Google</a>
@endif
        <p class="text-center m-0 mt-4 tx-mute">Belum punya akun? <a href="{{ route('register') }}" class="lnk">Daftar sekarang</a></p>
      </div>
    </div>
  </main>
</div>
<script>document.getElementById('yr').textContent=new Date().getFullYear();
document.querySelectorAll('[data-toggle]').forEach(function(b){b.addEventListener('click',function(){var i=document.getElementById(b.dataset.toggle),s=i.type==='password';i.type=s?'text':'password';b.firstElementChild.textContent=s?'visibility_off':'visibility'})});
</script>
</body>
</html>
