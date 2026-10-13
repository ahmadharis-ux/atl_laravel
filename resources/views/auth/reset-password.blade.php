@extends('layouts.auth')

@section('title', 'Atur Ulang Kata Sandi - Atlantic Pedia Tab')

@section('content')
  <h1 class="fw-bold mb-1" style="font-size:1.625rem;letter-spacing:-.015em">Atur ulang kata sandi</h1>
  <p class="tx-mute mb-4">Buat kata sandi baru untuk akun Anda.</p>

  @if ($errors->any())
    <div class="alert-ap alert-err mb-3">{{ $errors->first() }}</div>
  @endif

  <form action="{{ route('password.update') }}" method="POST" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="field">
      <label for="email">Email</label>
      <div class="inp">
        <span class="ms">person</span>
        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" placeholder="nama@email.com" autocomplete="username" required>
      </div>
    </div>
    <div class="field">
      <label for="password">Kata sandi baru</label>
      <div class="inp">
        <span class="ms">lock</span>
        <input id="password" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
        <button type="button" class="toggle" data-toggle="password" aria-label="Tampilkan atau sembunyikan kata sandi"><span class="ms">visibility</span></button>
      </div>
    </div>
    <div class="field">
      <label for="password_confirmation">Konfirmasi kata sandi</label>
      <div class="inp">
        <span class="ms">lock_reset</span>
        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi kata sandi" autocomplete="new-password" required>
      </div>
    </div>
    <button type="submit" class="btn-main mb-3">Simpan kata sandi</button>
  </form>
  <p class="tx-mute mb-0 text-center fs-12"><a href="{{ route('login') }}" class="lnk">Kembali ke halaman masuk</a></p>
@endsection
