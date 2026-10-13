@extends('layouts.auth')

@section('title', 'Verifikasi Dua Faktor - Atlantic Pedia Tab')

@section('content')
  <div class="text-center mb-3"><span class="ms" style="font-size:44px;color:var(--teal)">verified_user</span></div>
  <h1 class="fw-bold mb-1" style="font-size:1.625rem;letter-spacing:-.015em">Verifikasi dua faktor</h1>
  <p class="tx-mute mb-4">Masukkan kode 6 digit dari aplikasi authenticator Anda, atau salah satu kode pemulihan.</p>

  @if ($errors->any())
    <div class="alert-ap alert-err mb-3">{{ $errors->first() }}</div>
  @endif

  <form action="{{ route('two-factor.challenge.verify') }}" method="POST" novalidate>
    @csrf
    <div class="field">
      <label for="code">Kode verifikasi</label>
      <input id="code" name="code" class="code-inp" inputmode="text" autocomplete="one-time-code" placeholder="000000" autofocus>
    </div>
    <button type="submit" class="btn-main mb-3">Verifikasi</button>
  </form>
  <p class="tx-mute mb-0 text-center fs-12">Bukan Anda? <a href="{{ route('login') }}" class="lnk">Kembali ke halaman masuk</a></p>
@endsection
