// Toggle sidebar collapse
(function () {
  var toggles = document.querySelectorAll('.js-sidebar-toggle');
  if (!toggles.length) return;
  var collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
  if (collapsed) document.documentElement.classList.add('sidebar-collapsed');
  toggles.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      collapsed = !collapsed;
      document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
      localStorage.setItem('sidebar-collapsed', collapsed ? 'true' : 'false');
    });
  });
})();

// Submenu sidebar (Customer Topup, dsb.)
(function () {
  var toggles = document.querySelectorAll('[data-side-toggle]');
  if (!toggles.length) return;
  toggles.forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (document.documentElement.classList.contains('sidebar-collapsed')) return;
      var group = btn.closest('.side-group');
      if (!group) return;
      var open = group.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
})();

// Drawer sidebar admin (mobile/tablet)
(function () {
  var sidebar = document.querySelector('[data-admin-sidebar]');
  if (!sidebar) return;
  var backdrops = document.querySelectorAll('[data-admin-sidebar-close]');
  var openers = document.querySelectorAll('.js-sidebar-mobile-toggle');
  function isDesktop() { return window.matchMedia('(min-width: 992px)').matches; }
  function open() {
    if (isDesktop()) return;
    sidebar.classList.add('is-open');
    backdrops.forEach(function (b) { b.classList.add('is-open'); });
    document.body.style.overflow = 'hidden';
    openers.forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
  }
  function close() {
    sidebar.classList.remove('is-open');
    backdrops.forEach(function (b) { b.classList.remove('is-open'); });
    document.body.style.overflow = '';
    openers.forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
  }
  openers.forEach(function (b) {
    b.addEventListener('click', function () {
      if (sidebar.classList.contains('is-open')) close(); else open();
    });
  });
  backdrops.forEach(function (b) { b.addEventListener('click', close); });
  // Tutup saat menavigasi lewat tautan di dalam drawer.
  sidebar.addEventListener('click', function (e) {
    if (e.target.closest('a')) close();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  window.addEventListener('resize', close);
})();

// Sembunyikan / tampilkan saldo
(function () {
  var btn = document.getElementById('eye');
  var el = document.getElementById('saldo');
  if (!btn || !el) return;
  var hidden = false, real = el.textContent;
  btn.addEventListener('click', function () {
    hidden = !hidden;
    el.textContent = hidden ? '••••••' : real;
    btn.firstElementChild.textContent = hidden ? 'visibility_off' : 'visibility';
  });
})();