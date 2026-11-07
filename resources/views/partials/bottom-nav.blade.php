<nav class="bnav d-lg-none" aria-label="Navigasi utama">
    <div class="grid5">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'on' : '' }}"><span class="ms f">home</span>Beranda</a>
        <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'on' : '' }}"><span class="ms">storefront</span>Produk</a>
        <a href="{{ route('saldo.index') }}" class="mid {{ request()->routeIs('saldo.*') ? 'on' : '' }}"><span class="fab"><span class="ms ms-lg">add</span></span>Saldo</a>
        <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'on' : '' }}"><span class="ms">schedule</span>Riwayat</a>
        <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'on' : '' }}"><span class="ms">person</span>Akun</a>
    </div>
</nav>
