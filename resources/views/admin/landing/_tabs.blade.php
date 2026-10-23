<nav class="ptabs">
    <a href="{{ route('admin.landing.settings') }}" @class(['active' => request()->routeIs('admin.landing.settings')]) @if (request()->routeIs('admin.landing.settings')) aria-current="page" @endif>Pengaturan</a>
    <a href="{{ route('admin.landing.services') }}" @class(['active' => request()->routeIs('admin.landing.services')]) @if (request()->routeIs('admin.landing.services')) aria-current="page" @endif>Layanan</a>
    <a href="{{ route('admin.landing.testimonials') }}" @class(['active' => request()->routeIs('admin.landing.testimonials')]) @if (request()->routeIs('admin.landing.testimonials')) aria-current="page" @endif>Testimoni</a>
    <a href="{{ route('admin.landing.faqs') }}" @class(['active' => request()->routeIs('admin.landing.faqs')]) @if (request()->routeIs('admin.landing.faqs')) aria-current="page" @endif>FAQ</a>
</nav>
