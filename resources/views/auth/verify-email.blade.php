@extends('layouts.auth')

@section('title', 'Verifikasi Email - Atlantic Pedia Tab')

@section('content')
  <div class="text-center mb-3"><span class="ms" style="font-size:44px;color:var(--mintt)">mark_email_unread</span></div>
  <h1 class="fw-bold mb-1" style="font-size:1.625rem;letter-spacing:-.015em">Verifikasi email Anda</h1>
  <p class="tx-mute mb-4">Kami telah mengirim tautan verifikasi ke <b class="tx-deep">{{ auth()->user()->email }}</b>. Klik tautan tersebut untuk mengaktifkan akun Anda.</p>

  @if (session('status'))
    <div class="alert-ap alert-ok mb-3">{{ session('status') }}</div>
  @endif

  <form action="{{ route('verification.send') }}" method="POST" class="mb-2">
    @csrf
    <button type="submit" class="btn-main">Kirim ulang email verifikasi</button>
  </form>
  <form action="{{ route('logout') }}" method="POST">
    @csrf
    <button type="submit" class="btn-alt">Keluar</button>
  </form>
@endsection
