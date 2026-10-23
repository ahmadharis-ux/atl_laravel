@extends('layouts.landing')

@section('title', 'Atlantic Pedia — PPOB, Pulsa & Top Up Digital')
@section('meta_description', $settings->meta_description ?: 'Atlantic Pedia — layanan PPOB, pulsa, paket data, token listrik, dan produk digital dengan proses instan.')

@php
    $link = fn (?string $url, string $fallback): string => filled($url)
        ? (\Illuminate\Support\Str::startsWith($url, ['http://', 'https://']) ? $url : url($url))
        : $fallback;
    $registerUrl = route('register');
    $loginUrl = route('login');
@endphp

@section('content')
    <section class="landing-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    @if ($settings->hero_badge)
                        <span class="landing-badge">
                            <span class="ms ms-sm">verified</span>{{ $settings->hero_badge }}
                        </span>
                    @endif
                    <h1 class="landing-title mt-3">{{ $settings->hero_title }}</h1>
                    <p class="landing-subtitle mt-3">{{ $settings->hero_subtitle }}</p>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="{{ $link($settings->hero_cta_url, $registerUrl) }}" class="btn-ap btn-mint">
                            <span class="ms ms-sm me-1">rocket_launch</span>{{ $settings->hero_cta_label ?: 'Mulai Transaksi' }}
                        </a>
                        <a href="{{ $link($settings->hero_secondary_url, '#layanan') }}" class="btn-ap landing-btn-ghost">
                            <span class="ms ms-sm me-1">storefront</span>{{ $settings->hero_secondary_label ?: 'Lihat Layanan' }}
                        </a>
                    </div>

                    <div class="landing-trust d-flex flex-wrap gap-4 mt-4">
                        <span><span class="ms ms-sm">bolt</span>Proses instan</span>
                        <span><span class="ms ms-sm">shield_lock</span>Pembayaran aman</span>
                        <span><span class="ms ms-sm">support_agent</span>CS 24/7</span>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="landing-preview">
                        <div class="landing-preview-card">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-12" style="opacity:.75">Saldo Atlantic Pedia</span>
                                <span class="pill bg-glass" style="color:#d9fbff"><span class="ms ms-sm me-1">account_balance_wallet</span>Wallet</span>
                            </div>
                            <div class="num landing-preview-balance">Rp 250.000</div>
                            <div class="landing-preview-actions">
                                <span><span class="ms">add_card</span>Top Up</span>
                                <span><span class="ms">sync_alt</span>Transfer</span>
                                <span><span class="ms">receipt_long</span>Riwayat</span>
                            </div>
                        </div>

                        <div class="landing-preview-item">
                            <span class="ibox ibox-40 bg-mint-l tx-mintt"><span class="ms">smartphone</span></span>
                            <div class="flex-fill">
                                <div class="fw-bold fs-13">Pulsa Telkomsel 25.000</div>
                                <div class="fs-11" style="opacity:.7">0812 •••• 7890</div>
                            </div>
                            <span class="pill bg-mint-l tx-mintt">Sukses</span>
                        </div>

                        <div class="landing-preview-item">
                            <span class="ibox ibox-40 bg-gold tx-gold"><span class="ms">electric_bolt</span></span>
                            <div class="flex-fill">
                                <div class="fw-bold fs-13">Token Listrik 100.000</div>
                                <div class="fs-11" style="opacity:.7">ID Pelanggan 5321 ••••</div>
                            </div>
                            <span class="pill bg-mint-l tx-mintt">Sukses</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="landing-stats mt-5">
                <div class="row g-3">
                    <div class="col-6 col-lg-3">
                        <div class="landing-stat"><span class="num">{{ $settings->stat_customers ?: '25.000+' }}</span><small>Pelanggan aktif</small></div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="landing-stat"><span class="num">{{ $settings->stat_transactions ?: '1,2 Jt+' }}</span><small>Transaksi diproses</small></div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="landing-stat"><span class="num">{{ $settings->stat_products ?: '500+' }}</span><small>Produk digital</small></div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="landing-stat"><span class="num">{{ $settings->stat_support ?: '24/7' }}</span><small>Dukungan pelanggan</small></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="layanan" class="landing-section">
        <div class="container">
            <div class="landing-section-head">
                <span class="pill bg-tealc-m tx-tealt">Our Service</span>
                <h2>Layanan yang kami sediakan</h2>
                <p>Semua kebutuhan digital dalam satu akun, dari pulsa sampai voucher game.</p>
            </div>

            <div class="row g-4">
                @forelse ($services as $service)
                    <div class="col-md-6 col-lg-4">
                        <article class="landing-service">
                            <span class="landing-service-ic"><span class="ms">{{ $service->icon }}</span></span>
                            <h3>{{ $service->title }}</h3>
                            <p>{{ $service->description }}</p>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="tx-mute text-center m-0">Layanan akan segera hadir.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section id="tentang" class="landing-section landing-about">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="pill bg-mint-l tx-mintt">Tentang Kami</span>
                    <h2 class="landing-section-title mt-3">{!! $settings->about_title ?: 'Kenapa Atlantic Pedia?' !!}</h2>
                    <p class="landing-section-desc mt-3">{{ $settings->about_body }}</p>

                    <ul class="landing-benefits">
                        <li><span class="ms">check_circle</span>Harga bersaing untuk pengguna maupun reseller.</li>
                        <li><span class="ms">check_circle</span>Pembayaran fleksibel: QRIS, transfer bank, dan e-wallet.</li>
                        <li><span class="ms">check_circle</span>Riwayat transaksi rapi lengkap dengan invoice.</li>
                        <li><span class="ms">check_circle</span>API reseller untuk integrasi otomatis.</li>
                    </ul>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="{{ $registerUrl }}" class="btn-ap btn-navy">Daftar Sekarang</a>
                        <a href="{{ $loginUrl }}" class="btn-ap btn-soft">Sudah punya akun? Masuk</a>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="landing-about-card">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="ibox ibox-44 bg-tealc-m tx-tealt"><span class="ms">hub</span></span>
                            <div>
                                <div class="fw-bold">Satu akun, banyak kebutuhan</div>
                                <div class="tx-mute fs-12">Dirancang untuk pengguna harian &amp; mitra bisnis.</div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-6"><div class="landing-mini"><span class="ms">payments</span><b>Deposit cepat</b><small>QRIS &amp; transfer</small></div></div>
                            <div class="col-6"><div class="landing-mini"><span class="ms">receipt_long</span><b>Invoice otomatis</b><small>Siap diunduh</small></div></div>
                            <div class="col-6"><div class="landing-mini"><span class="ms">api</span><b>API reseller</b><small>Integrasi toko</small></div></div>
                            <div class="col-6"><div class="landing-mini"><span class="ms">notifications</span><b>Notifikasi</b><small>Status real-time</small></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="testimoni" class="landing-section">
        <div class="container">
            <div class="landing-section-head">
                <span class="pill bg-gold tx-gold">Testimoni</span>
                <h2>Apa kata pengguna kami</h2>
                <p>Cerita nyata dari pelanggan dan mitra Atlantic Pedia.</p>
            </div>

            <div class="row g-4">
                @forelse ($testimonials as $testimonial)
                    <div class="col-md-6 col-lg-4">
                        <article class="landing-testimonial">
                            <div class="landing-stars" aria-label="Rating {{ $testimonial->rating }} dari 5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <span class="ms ms-sm {{ $i <= $testimonial->rating ? 'f' : '' }}">star</span>
                                @endfor
                            </div>
                            <p class="landing-testimonial-msg">“{{ $testimonial->message }}”</p>
                            <div class="d-flex align-items-center gap-2 mt-3">
                                <span class="ibox bg-tealc-m tx-tealt fw-bold rounded-circle" style="width:2.25rem;height:2.25rem">{{ strtoupper(substr($testimonial->name, 0, 1)) }}</span>
                                <div>
                                    <div class="fw-bold fs-13">{{ $testimonial->name }}</div>
                                    <div class="tx-mute fs-12">{{ $testimonial->role }}</div>
                                </div>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12"><p class="tx-mute text-center m-0">Belum ada testimoni.</p></div>
                @endforelse
            </div>
        </div>
    </section>

    <section id="faq" class="landing-section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <span class="pill bg-tealc-m tx-tealt">FAQ</span>
                    <h2 class="landing-section-title mt-3">Pertanyaan yang sering diajukan</h2>
                    <p class="landing-section-desc mt-3">Belum menemukan jawaban? Hubungi tim CS kami lewat WhatsApp.</p>
                    @if ($waLink = \App\Support\SupportWhatsapp::generalUrl())
                        <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn-ap btn-navy mt-2">
                            <span class="ms ms-sm me-1">chat</span>Chat Admin
                        </a>
                    @endif
                </div>
                <div class="col-lg-8">
                    <div class="accordion landing-faq" id="landingFaq">
                        @foreach ($faqs as $index => $faq)
                            <div class="accordion-item">
                                <h3 class="accordion-header" id="faqHead{{ $faq->id }}">
                                    <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faqBody{{ $faq->id }}"
                                        aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="faqBody{{ $faq->id }}">
                                        {{ $faq->question }}
                                    </button>
                                </h3>
                                <div id="faqBody{{ $faq->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                                    aria-labelledby="faqHead{{ $faq->id }}" data-bs-parent="#landingFaq">
                                    <div class="accordion-body">{{ $faq->answer }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-cta-wrap">
        <div class="container">
            <div class="landing-cta">
                <div>
                    <h2>Siap mulai bertransaksi?</h2>
                    <p>Daftar gratis dan nikmati kemudahan transaksi digital bersama Atlantic Pedia.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ $registerUrl }}" class="btn-ap btn-mint">Buat Akun Gratis</a>
                    @if ($waLink = \App\Support\SupportWhatsapp::generalUrl())
                        <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn-ap landing-btn-ghost">
                            <span class="ms ms-sm me-1">chat</span>Tanya CS
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
