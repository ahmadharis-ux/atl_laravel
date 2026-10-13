@extends('layouts.auth')

@section('title', 'Lupa Kata Sandi - Atlantic Pedia Tab')

@section('content')
  <h1 class="fw-bold mb-1" style="font-size:1.625rem;letter-spacing:-.015em">Lupa kata sandi?</h1>
  <p class="tx-mute mb-4">Masukkan email terdaftar, kami akan mengirim tautan untuk mengatur ulang kata sandi Anda.</p>

  @if (session('status'))
    <div class="alert-ap alert-ok mb-3">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert-ap alert-err mb-3">{{ $errors->first() }}</div>
  @endif

  <form action="{{ route('password.email') }}" method="POST" novalidate>
    @csrf
    <div class="field">
      <label for="email">Email</label>
      <div class="inp">
        <span class="ms">mail</span>
        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="username" required autofocus>
      </div>
    </div>
    <button type="submit" class="btn-main mb-3">Kirim tautan reset</button>
  </form>
  <p class="tx-mute mb-0 text-center fs-12"><a href="{{ route('login') }}" class="lnk">Kembali ke halaman masuk</a></p>
@endsection
