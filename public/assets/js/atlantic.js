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