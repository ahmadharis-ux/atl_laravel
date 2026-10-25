// Atlantic Pedia - generic UI component behaviors
// Handles: data table (search/filter/bulk), wizard, charts, rich text editor.
// Libraries (Chart.js, Quill) are lazy-loaded only when their markup is present.
(function () {
  'use strict';

  function loadScript(src) {
    return new Promise(function (resolve) {
      if ([...document.scripts].some(s => s.src === src)) return resolve();
      var s = document.createElement('script');
      s.src = src;
      s.onload = resolve;
      s.onerror = resolve;
      document.head.appendChild(s);
    });
  }

  function loadStyle(href) {
    return new Promise(function (resolve) {
      if (document.querySelector('link[href="' + href + '"]')) return resolve();
      var l = document.createElement('link');
      l.rel = 'stylesheet';
      l.href = href;
      l.onload = resolve;
      l.onerror = resolve;
      document.head.appendChild(l);
    });
  }

  // ---------------- Data table ----------------
  function initTables() {
    document.querySelectorAll('[data-datatable]').forEach(function (root) {
      var search = root.querySelector('.js-dt-search');
      var filters = [...root.querySelectorAll('[data-dt-filter]')];
      var checkAll = root.querySelector('.js-dt-checkall');
      var bar = root.querySelector('.js-dt-bulk');
      var countEl = root.querySelector('.js-dt-count');
      var tbody = root.querySelector('tbody');
      if (!tbody) return;

      var rows = () => [...tbody.querySelectorAll('tr')];
      var boxes = () => rows().map(r => r.querySelector('.row-check')).filter(Boolean);
      var selected = () => boxes().filter(c => c.checked);

      function matches(tr) {
        var text = tr.textContent.toLowerCase();
        if (search && search.value && text.indexOf(search.value.toLowerCase()) === -1) return false;
        return filters.every(function (f) {
          if (!f.value) return true;
          return (tr.dataset[f.dataset.col] || '') === f.value;
        });
      }

      function sync() {
        if (countEl) countEl.textContent = selected().length;
        if (bar) {
          var n = selected().length;
          bar.classList.toggle('d-none', n === 0);
          bar.classList.toggle('d-flex', n > 0);
        }
        if (checkAll) {
          var all = boxes(), n = selected().length;
          checkAll.checked = n > 0 && n === all.length;
          checkAll.indeterminate = n > 0 && n < all.length;
        }
        rows().forEach(function (tr) {
          var c = tr.querySelector('.row-check');
          tr.classList.toggle('is-selected', !!(c && c.checked));
        });
      }

      function apply() {
        rows().forEach(function (tr) { tr.style.display = matches(tr) ? '' : 'none'; });
        sync();
      }

      if (search) search.addEventListener('input', apply);
      filters.forEach(f => f.addEventListener('change', apply));

      if (checkAll) checkAll.addEventListener('change', function () {
        boxes().forEach(c => { c.checked = checkAll.checked; });
        sync();
      });

      tbody.addEventListener('change', function (e) {
        if (e.target.classList.contains('row-check')) sync();
      });

      var clear = root.querySelector('.js-dt-clear');
      if (clear) clear.addEventListener('click', function () {
        boxes().forEach(c => { c.checked = false; });
        if (checkAll) checkAll.checked = false;
        sync();
      });

      if (bar) bar.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-dt-action]');
        if (!btn) return;
        var n = selected().length;
        if (btn.dataset.dtAction === 'delete') {
          if (!confirm('Hapus ' + n + ' data terpilih?')) return;
          selected().forEach(c => c.closest('tr').remove());
          if (checkAll) checkAll.checked = false;
          sync();
        } else if (btn.dataset.dtModal) {
          var modal = document.getElementById(btn.dataset.dtModal);
          if (!modal) return;
          var holder = modal.querySelector('[data-bulk-ids]');
          if (holder) {
            holder.innerHTML = '';
            var fieldName = holder.dataset.bulkField || 'product_ids[]';
            selected().forEach(function (c) {
              var input = document.createElement('input');
              input.type = 'hidden';
              input.name = fieldName;
              input.value = c.value;
              holder.appendChild(input);
            });
          }
          var modalCount = modal.querySelector('[data-bulk-count]');
          if (modalCount) modalCount.textContent = n;
          openModal(btn.dataset.dtModal);
        } else {
          alert('Aksi "' + btn.dataset.dtAction + '" untuk ' + n + ' data terpilih.');
        }
      });

      sync();
    });
  }

  // ---------------- Wizard ----------------
  function initWizards() {
    document.querySelectorAll('.js-wizard').forEach(function (root) {
      var steps = [...root.querySelectorAll('.js-wz-steps .wz-step')];
      var panels = [...root.querySelectorAll('.wz-panel')];
      var prev = root.querySelector('.js-wz-prev');
      var next = root.querySelector('.js-wz-next');
      var submit = root.querySelector('.js-wz-submit');
      if (!steps.length) return;
      var current = 1;

      function render() {
        steps.forEach(function (s) {
          var n = +s.dataset.step;
          s.classList.toggle('active', n === current);
          s.classList.toggle('done', n < current);
        });
        panels.forEach(function (p) {
          p.classList.toggle('active', +p.dataset.panel === current);
        });
        if (prev) prev.disabled = current === 1;
        if (next) next.classList.toggle('d-none', current === steps.length);
        if (submit) submit.classList.toggle('d-none', current !== steps.length);
      }

      if (next) next.addEventListener('click', function () { if (current < steps.length) { current++; render(); } });
      if (prev) prev.addEventListener('click', function () { if (current > 1) { current--; render(); } });
      if (submit) submit.addEventListener('click', function () {
        root.dispatchEvent(new CustomEvent('wizard:submit', { bubbles: true }));
      });
      steps.forEach(function (s) {
        s.addEventListener('click', function () { current = +s.dataset.step; render(); });
      });
      render();
    });
  }

  // ---------------- Charts ----------------
  function initCharts() {
    var nodes = [...document.querySelectorAll('script.js-chart')];
    if (!nodes.length) return;
    loadScript('https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js').then(function () {
      if (!window.Chart) return;
      Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
      Chart.defaults.color = '#42474c';
      nodes.forEach(function (n) {
        var canvas = document.getElementById(n.dataset.target);
        if (!canvas) return;
        var cfg = JSON.parse(n.textContent);
        new Chart(canvas, {
          type: n.dataset.type,
          data: cfg.data,
          options: Object.assign({ responsive: true, maintainAspectRatio: false }, cfg.options || {}),
        });
      });
    });
  }

  // ---------------- Rich text editor ----------------
  function initEditors() {
    var els = [...document.querySelectorAll('.js-editor')];
    if (!els.length) return;
    Promise.all([
      loadStyle('https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css'),
      loadScript('https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js'),
    ]).then(function () {
      if (!window.Quill) return;
      els.forEach(function (el) {
        var quill = new Quill(el, {
          theme: 'snow',
          modules: {
            toolbar: [
              [{ header: [2, 3, false] }],
              ['bold', 'italic', 'underline'],
              [{ list: 'ordered' }, { list: 'bullet' }],
              ['link', 'blockquote'],
              ['clean'],
            ],
          },
        });
        el._quill = quill;
        var name = el.dataset.name;
        if (name) {
          var input = document.querySelector('.js-editor-input[name="' + name + '"]');
          if (input) {
            input.value = quill.root.innerHTML;
            quill.on('text-change', function () { input.value = quill.root.innerHTML; });
          }
        }
      });
    });
  }

  // ---------------- Searchable select ----------------
  // Enhances <select class="js-search-select"> (via <x-ui.search-select>).
  // With a data-remote-url the options are fetched on demand (server-side
  // search) so large lists never bloat the initial page load.
  function initSearchSelects(scope) {
    var root = (scope && scope.querySelectorAll) ? scope : document;
    var els = [...root.querySelectorAll('select.js-search-select')].filter(function (el) { return !el.tomselect; });
    if (!els.length) return;
    Promise.all([
      loadStyle('https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css'),
      loadScript('https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js'),
    ]).then(function () {
      if (!window.TomSelect) return;
      els.forEach(function (el) {
        if (el.tomselect) return;
        var remote = el.dataset.remoteUrl;
        var dependsOn = el.dataset.dependsOn || null;
        var cfg = {
          create: false,
          allowEmptyOption: true,
          placeholder: el.dataset.placeholder || '',
          maxOptions: null,
        };

        // Nilai field induk (mis. kategori untuk pilihan brand).
        function parentValue() {
          if (!dependsOn || !el.form || !el.form.elements[dependsOn]) return '';
          return el.form.elements[dependsOn].value || '';
        }

        if (remote) {
          cfg.valueField = 'value';
          cfg.labelField = 'text';
          cfg.searchField = ['text'];
          cfg.loadThrottle = 300;
          cfg.preload = 'focus'; // muat beberapa opsi sebagai hint saat dropdown dibuka
          var minChars = parseInt(el.dataset.minChars, 10) || 1;
          var hintLimit = parseInt(el.dataset.hintLimit, 10) || 5;
          cfg.shouldLoad = function (query) { return query.length === 0 || query.length >= minChars; };
          cfg.load = function (query, callback) {
            var empty = query.length === 0;
            var limit = empty ? hintLimit : 20;
            var url = remote + (remote.indexOf('?') === -1 ? '?' : '&')
              + 'q=' + encodeURIComponent(query) + '&limit=' + limit;
            var parent = parentValue();
            if (parent) {
              url += '&' + encodeURIComponent(dependsOn) + '=' + encodeURIComponent(parent);
            }
            fetch(url, {
              headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
              .then(function (r) { return r.ok ? r.json() : { results: [] }; })
              .then(function (data) { callback(data.results || data || []); })
              .catch(function () { callback(); });
          };
        }

        el.tomselect = new TomSelect(el, cfg);

        // Select turunan: saat field induk berubah, buang opsi lawas lalu muat ulang.
        if (dependsOn && el.form && el.form.elements[dependsOn]) {
          el.form.elements[dependsOn].addEventListener('change', function () {
            var ts = el.tomselect;
            if (!ts) return;
            ts.clear(true);
            ts.clearOptions();
            ts.load('');
          });
        }
      });
    });
  }

  // ---------------- Multi-select (searchable) ----------------
  // Enhances <select multiple class="js-multi-select"> (via <x-ui.multi-select>).
  // With a data-remote-url the options are fetched on demand as JSON
  // { results: [{ value, text }] }, so large lists stay light.
  function initMultiSelects(scope) {
    var root = (scope && scope.querySelectorAll) ? scope : document;
    var els = [...root.querySelectorAll('select.js-multi-select')].filter(function (el) { return !el.tomselect; });
    if (!els.length) return;
    Promise.all([
      loadStyle('https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css'),
      loadScript('https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js'),
    ]).then(function () {
      if (!window.TomSelect) return;
      els.forEach(function (el) {
        if (el.tomselect) return;
        var remote = el.dataset.remoteUrl;
        var cfg = {
          create: false,
          plugins: ['remove_button'],
          maxItems: null,
          placeholder: el.dataset.placeholder || '',
          maxOptions: null,
        };

        if (remote) {
          cfg.valueField = 'value';
          cfg.labelField = 'text';
          cfg.searchField = ['text'];
          cfg.loadThrottle = 300;
          cfg.preload = 'focus';
          cfg.load = function (query, callback) {
            var url = new URL(remote, window.location.origin);
            url.searchParams.set('q', query || '');
            fetch(url.toString(), {
              headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            })
              .then(function (r) { return r.ok ? r.json() : { results: [] }; })
              .then(function (data) { callback(data.results || data || []); })
              .catch(function () { callback(); });
          };
        }

        el.tomselect = new TomSelect(el, cfg);
      });
    });
  }

  // ---------------- AJAX list (filter tanpa reload / tanpa ubah URL) ----------------
  // Markup: <div data-ajax data-ajax-url="... " data-persist-key="...">
  //   <form data-ajax-form> ... </form>   (opsional; submit & change memicu load)
  //   <div data-ajax-rows> ... </div>     (wajib; tempat hasil diganti)
  //   <div data-ajax-footer> ... </div>   (opsional; pagination diganti)
  //   <button data-ajax-reset> ... </button> (opsional)
  // State filter disimpan di localStorage dan dipulihkan saat refresh.
  function initAjaxTables() {
    document.querySelectorAll('[data-ajax]').forEach(function (root) {
      var url = root.dataset.ajaxUrl;
      var form = root.querySelector('[data-ajax-form]');
      var rowsEl = root.querySelector('[data-ajax-rows]');
      var tabsEl = root.querySelector('[data-ajax-tabs]');
      var footEl = root.querySelector('[data-ajax-footer]');
      var modalEl = root.querySelector('[data-ajax-modals]');
      var key = root.dataset.persistKey;
      if (!url || !rowsEl) return;

      var controller = null;
      var timer = null;

      function collectFields() {
        var data = {};
        if (form) {
          new FormData(form).forEach(function (v, k) {
            if (k === '_token' || k === '_method' || k === 'page') return;
            data[k] = v;
          });
        }
        root.querySelectorAll('[data-ajax-maybe]').forEach(function (el) {
          var field = el.matches('input,select,textarea') ? el : el.querySelector('input,select,textarea');
          if (!field || !field.name) return;
          var val = (field.type === 'checkbox' || field.type === 'radio')
            ? (field.checked ? field.value : '')
            : field.value;
          if (val) data[field.name] = val;
        });
        return data;
      }

      function persist() {
        if (!key || !form) return;
        var data = collectFields();
        form.querySelectorAll('select[data-remote-url]').forEach(function (el) {
          if (el.tomselect && el.value) {
            var item = el.tomselect.getItem(el.value);
            if (item) data['__text_' + el.name] = item.text;
          }
        });
        try { localStorage.setItem(key, JSON.stringify(data)); } catch (e) {}
      }

      function applyValues(data) {
        var hasValue = false;
        Object.keys(data).forEach(function (k) {
          if (k.indexOf('__text_') === 0) return;
          var el = form.elements[k];
          if (!el) {
            root.querySelectorAll('[data-ajax-maybe]').forEach(function (m) {
              var f = m.matches('input,select,textarea') ? m : m.querySelector('input,select,textarea');
              if (f && f.name === k) el = f;
            });
          }
          if (!el) return;
          var val = data[k];
          if (typeof RadioNodeList !== 'undefined' && el instanceof RadioNodeList) {
            Array.prototype.forEach.call(el, function (r) { r.checked = (r.value === val); });
            if (val !== '' && val != null) hasValue = true;
            return;
          }
          if (el.tomselect) {
            var text = data['__text_' + k];
            if (el.dataset.remoteUrl && val && text && !el.tomselect.options[val]) {
              var o = document.createElement('option');
              o.value = val;
              o.textContent = text;
              el.appendChild(o);
              el.tomselect.addOption({ value: val, text: text });
            }
            el.tomselect.setValue(val, true);
          } else {
            el.value = val;
            // Sinkronkan tampilan ber-titik untuk input mata uang.
            var currencyWrap = el.closest ? el.closest('[data-currency]') : null;
            if (currencyWrap) {
              var currencyDisplay = currencyWrap.querySelector('[data-currency-display]');
              if (currencyDisplay) {
                currencyDisplay.value = val ? String(val).replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
              }
            }
          }
          if (val !== '' && val != null) hasValue = true;
        });
        return hasValue;
      }

      function restore() {
        if (!key || !form) return false;
        var raw = null;
        try { raw = localStorage.getItem(key); } catch (e) {}
        if (!raw) return false;
        var data;
        try { data = JSON.parse(raw); } catch (e) { return false; }
        return applyValues(data);
      }

      // URL selalu menang (mis. pencarian dari beranda -> /produk?q=...)
      function readUrl() {
        var data = {};
        var found = false;
        try {
          new URLSearchParams(window.location.search).forEach(function (v, k) {
            if (k === 'page' || k === '_token') return;
            if (form && form.elements[k]) {
              data[k] = v;
              found = true;
              return;
            }
            root.querySelectorAll('[data-ajax-maybe]').forEach(function (el) {
              var f = el.matches('input,select,textarea') ? el : el.querySelector('input,select,textarea');
              if (f && f.name === k) {
                data[k] = v;
                found = true;
              }
            });
          });
        } catch (e) { return null; }
        return found ? data : null;
      }

      var load = root._load = function (page, append) {
        if (page == null) page = 1;
        var params = new URLSearchParams(collectFields());
        params.set('page', page);
        if (controller && controller.abort) controller.abort();
        controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
        root.classList.add('is-loading');
        fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + params.toString(), {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          signal: controller ? controller.signal : undefined,
        })
          .then(function (r) { return r.ok ? r.json() : null; })
          .then(function (data) {
            if (!data) return;
            if (typeof data.rows === 'string') {
              if (append) {
                var tmp = document.createElement('div');
                tmp.innerHTML = data.rows;
                while (tmp.firstChild) rowsEl.appendChild(tmp.firstChild);
              } else {
                rowsEl.innerHTML = data.rows;
              }
            }
            if (tabsEl && typeof data.tabs === 'string') tabsEl.innerHTML = data.tabs;
            if (footEl && typeof data.footer === 'string') footEl.innerHTML = data.footer;
            if (modalEl && typeof data.modals === 'string') modalEl.innerHTML = data.modals;
            // Baris/modal baru bisa memuat select pencarian; inisialisasi ulang.
            if (window.AP && typeof window.AP.initSelects === 'function') {
              window.AP.initSelects(rowsEl);
              if (modalEl) window.AP.initSelects(modalEl);
            }
            root.classList.remove('is-loading');
          })
          .catch(function (err) {
            if (!(err && err.name === 'AbortError')) root.classList.remove('is-loading');
          });
      }

      var urlData = readUrl();
      var restored = urlData ? applyValues(urlData) : restore();
      if (urlData && form) persist();

      var debounced = function () { persist(); load(1); };

      // Tutup modal filter yang sedang dipakai. Modal berada DI DALAM form,
      // jadi tidak bisa dicari lewat form.closest('.ap-modal').
      function closeFormModal(target) {
        var modal = (target && target.closest) ? target.closest('.ap-modal') : null;
        if (!modal && form) modal = form.querySelector('.ap-modal');
        if (modal) closeModal(modal);
      }

      if (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          persist();
          load(1);
          closeFormModal(e.submitter || e.target);
        });
        form.addEventListener('change', function (e) {
          if (e.target && e.target.closest && e.target.closest('[data-currency]')) return;
          clearTimeout(timer);
          timer = setTimeout(debounced, 250);
        });
        form.addEventListener('input', function (e) {
          if (e.target && e.target.tagName === 'SELECT') return;
          if (e.target && e.target.closest && e.target.closest('[data-currency]')) return;
          clearTimeout(timer);
          timer = setTimeout(debounced, 400);
        });
      }

      // Bidang di luar form (kotak pencarian produk) ikut difilter via AJAX.
      root.querySelectorAll('[data-ajax-maybe]').forEach(function (el) {
        var field = el.matches('input,select,textarea') ? el : el.querySelector('input,select,textarea');
        if (!field) return;
        if (el.matches('form')) {
          el.addEventListener('submit', function (e) { e.preventDefault(); debounced(); });
        }
        field.addEventListener('input', function (e) {
          if (e.target && e.target.tagName === 'SELECT') return;
          clearTimeout(timer);
          timer = setTimeout(debounced, 400);
        });
        field.addEventListener('change', function () {
          clearTimeout(timer);
          timer = setTimeout(debounced, 250);
        });
      });

      var reset = root.querySelector('[data-ajax-reset]');
      if (reset && form) {
        reset.addEventListener('click', function (e) {
          form.reset();
          form.querySelectorAll('select[data-remote-url]').forEach(function (el) {
            if (el.tomselect) el.tomselect.clear(true);
          });
          // form.reset() mengembalikan input mata uang ke nilai server, bukan kosong.
          form.querySelectorAll('[data-currency]').forEach(function (wrap) {
            var display = wrap.querySelector('[data-currency-display]');
            var hidden = wrap.querySelector('[data-currency-value]');
            if (display) display.value = '';
            if (hidden) hidden.value = '';
          });
          persist();
          load(1);
          closeFormModal(e.target);
        });
      }

      // Bersihkan kata kunci pencarian saja (di luar form), filter tetap.
      root.addEventListener('click', function (e) {
        var clear = e.target.closest('[data-ajax-clear-search]');
        if (!clear || !root.contains(clear)) return;
        e.preventDefault();
        root.querySelectorAll('[data-ajax-maybe]').forEach(function (el) {
          var field = el.matches('input,select,textarea') ? el : el.querySelector('input,select,textarea');
          if (field) field.value = '';
        });
        persist();
        load(1);
      });

      if (footEl) {
        footEl.addEventListener('click', function (e) {
          var more = e.target.closest('[data-ajax-more]');
          if (more && root.contains(more)) {
            e.preventDefault();
            if (root.classList.contains('is-loading')) return;
            load(more.dataset.page, true);
            return;
          }
          // Link paginasi bernomor (?page=N) dimuat via AJAX tanpa reload halaman,
          // supaya isian form di luar daftar (mis. ID/Zone) tidak hilang.
          var link = e.target.closest('a[href]');
          if (!link || !footEl.contains(link) || !root.contains(link)) return;
          if (link.closest('.disabled') || link.getAttribute('aria-disabled') === 'true') {
            e.preventDefault();
            return;
          }
          var page = null;
          try {
            page = new URL(link.getAttribute('href'), window.location.href).searchParams.get('page');
          } catch (err) {
            return;
          }
          if (!page) return;
          e.preventDefault();
          if (root.classList.contains('is-loading')) return;
          load(page, false);
          if (rowsEl.scrollIntoView) rowsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
      }

      if (restored) load(1);
    });
  }

  // ---------------- Tooltip ----------------
  function initTooltips() {
    var tip = document.createElement('div');
    tip.className = 'ap-tooltip';
    document.body.appendChild(tip);
    var current = null;

    function place(target) {
      var pos = target.dataset.tipPos || 'top';
      var r = target.getBoundingClientRect();
      var t = tip.getBoundingClientRect();
      var top, left, arrow;
      if (pos === 'bottom') {
        top = r.bottom + 9;
        left = r.left + r.width / 2 - t.width / 2;
        arrow = (r.left + r.width / 2) - left;
      } else if (pos === 'left') {
        top = r.top + r.height / 2 - t.height / 2;
        left = r.left - t.width - 9;
        arrow = (r.top + r.height / 2) - top;
      } else if (pos === 'right') {
        top = r.top + r.height / 2 - t.height / 2;
        left = r.right + 9;
        arrow = (r.top + r.height / 2) - top;
      } else {
        top = r.top - t.height - 9;
        left = r.left + r.width / 2 - t.width / 2;
        arrow = (r.left + r.width / 2) - left;
      }
      left = Math.max(8, Math.min(left, window.innerWidth - t.width - 8));
      top = Math.max(8, Math.min(top, window.innerHeight - t.height - 8));
      tip.style.left = left + 'px';
      tip.style.top = top + 'px';
      tip.dataset.pos = pos;
      tip.style.setProperty('--arrow', arrow + 'px');
    }

    function show(target) {
      if (!target.dataset.tip) return;
      current = target;
      tip.textContent = target.dataset.tip;
      tip.style.display = 'block';
      place(target);
      void tip.offsetWidth;
      tip.classList.add('show');
    }
    function hide() { current = null; tip.classList.remove('show'); }

    document.addEventListener('mouseover', function (e) {
      var t = e.target.closest('[data-tip]');
      if (t === current) return;
      if (t) show(t); else if (current) hide();
    });
    document.addEventListener('mouseout', function (e) {
      if (current && !e.relatedTarget) hide();
    });
    document.addEventListener('focusin', function (e) {
      var t = e.target.closest('[data-tip]');
      if (t) show(t);
    });
    document.addEventListener('focusout', function () { if (current) hide(); });
    document.addEventListener('click', hide);
    window.addEventListener('scroll', function () { if (current) hide(); }, true);
  }

  // ---------------- Row actions (kebab dropdown) ----------------
  function initActions() {
    var menu = document.createElement('div');
    menu.className = 'ap-menu';
    menu.hidden = true;
    document.body.appendChild(menu);
    var opener = null;

    function close() {
      if (!opener) return;
      opener.setAttribute('aria-expanded', 'false');
      opener = null;
      menu.classList.remove('show');
      menu.hidden = true;
    }
    function open(btn) {
      close();
      var wrap = btn.closest('[data-actions]');
      var tpl = wrap && wrap.querySelector('.ap-actions-template');
      menu.innerHTML = tpl ? tpl.innerHTML : '';
      menu.hidden = false;
      opener = btn;
      btn.setAttribute('aria-expanded', 'true');
      var r = btn.getBoundingClientRect();
      var mw = menu.offsetWidth, mh = menu.offsetHeight;
      var left = Math.max(8, Math.min(r.right - mw, window.innerWidth - mw - 8));
      var top = r.bottom + 6;
      if (top + mh > window.innerHeight - 8) top = r.top - mh - 6;
      menu.style.left = left + 'px';
      menu.style.top = Math.max(8, top) + 'px';
      requestAnimationFrame(function () { menu.classList.add('show'); });
    }

    document.addEventListener('click', function (e) {
      var toggle = e.target.closest('[data-actions-toggle]');
      if (toggle) {
        e.preventDefault();
        if (opener === toggle) close(); else open(toggle);
        return;
      }
      if (opener && !menu.contains(e.target)) close();
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    menu.addEventListener('click', function (e) { if (e.target.closest('.ap-menu-item')) close(); });
    window.addEventListener('resize', close);
    window.addEventListener('scroll', close, true);
  }

  // ---------------- Modal + Confirm ----------------
  function showModal(el) {
    if (!el) return;
    el.hidden = false;
    document.body.classList.add('ap-modal-open');
    var f = el.querySelector('[data-autofocus]') || el.querySelector('button,a,input,select,textarea');
    if (f) setTimeout(function () { f.focus(); }, 30);
  }
  function closeModal(el) {
    if (!el) return;
    el.hidden = true;
    if (!document.querySelector('.ap-modal:not([hidden])')) document.body.classList.remove('ap-modal-open');
  }
  function openModal(id) { showModal(document.getElementById(id)); }

  var confirmEl = null, confirmCb = null;
  function ensureConfirm() {
    if (confirmEl) return confirmEl;
    confirmEl = document.createElement('div');
    confirmEl.className = 'ap-modal';
    confirmEl.hidden = true;
    confirmEl.innerHTML =
      '<div class="ap-modal-backdrop" data-modal-close></div>' +
      '<div class="ap-modal-dialog ap-modal-sm" role="alertdialog" aria-modal="true">' +
        '<div class="ap-modal-head"><h3 class="ap-confirm-title">Konfirmasi</h3>' +
          '<button type="button" class="ap-icon-btn" data-modal-close data-tip="Tutup"><span class="ms ms-md">close</span></button></div>' +
        '<div class="ap-modal-body"><p class="ap-confirm-msg m-0">Anda yakin?</p></div>' +
        '<div class="ap-modal-foot">' +
          '<button type="button" class="btn-soft" data-modal-close>Batal</button>' +
          '<button type="button" class="btn-navy ap-confirm-ok">Ya, Lanjutkan</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(confirmEl);
    confirmEl.querySelector('.ap-confirm-ok').addEventListener('click', function () {
      var cb = confirmCb;
      closeModal(confirmEl);
      confirmCb = null;
      if (typeof cb === 'function') cb(true);
    });
    confirmEl.addEventListener('click', function (e) {
      if (e.target.closest('[data-modal-close]')) {
        var cb = confirmCb;
        closeModal(confirmEl);
        confirmCb = null;
        if (typeof cb === 'function') cb(false);
      }
    });
    return confirmEl;
  }
  function confirmAction(opts, cb) {
    var el = ensureConfirm();
    var o = typeof opts === 'string' ? { message: opts } : (opts || {});
    el.querySelector('.ap-confirm-title').textContent = o.title || 'Konfirmasi';
    el.querySelector('.ap-confirm-msg').textContent = o.message || 'Anda yakin?';
    var ok = el.querySelector('.ap-confirm-ok');
    ok.textContent = o.okLabel || 'Ya, Lanjutkan';
    ok.style.background = o.danger ? 'var(--err)' : '';
    confirmCb = typeof cb === 'function' ? cb : function(){};
    showModal(el);
  }

  function initModals() {
    document.addEventListener('click', function (e) {
      var opener = e.target.closest('[data-modal-open]');
      if (opener) { e.preventDefault(); openModal(opener.dataset.modalOpen); return; }

      var closer = e.target.closest('[data-modal-close]');
      if (closer) { e.preventDefault(); closeModal(closer.closest('.ap-modal')); return; }

      var trig = e.target.closest('[data-confirm]');
      if (trig) {
        e.preventDefault();
        var isLink = trig.tagName === 'A' && trig.getAttribute('href');
        confirmAction(
          {
            message: trig.dataset.confirm,
            title: trig.dataset.confirmTitle,
            okLabel: trig.dataset.confirmOk,
            danger: trig.hasAttribute('data-confirm-danger'),
          },
          function () {
            if (trig.dataset.submitForm) {
              var form = document.getElementById(trig.dataset.submitForm);
              if (form) { if (form.requestSubmit) form.requestSubmit(); else form.submit(); }
              return;
            }
            if (trig.dataset.href) { window.location = trig.dataset.href; return; }
            trig.dispatchEvent(new CustomEvent('confirm:accept', { bubbles: true }));
            if (isLink) window.location = trig.href;
          }
        );
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      var open = [...document.querySelectorAll('.ap-modal:not([hidden])')].pop();
      if (open) closeModal(open);
    });

    // Modal dengan atribut data-modal-auto dibuka otomatis saat halaman dimuat.
    document.querySelectorAll('.ap-modal[data-modal-auto]').forEach(function (el) {
      setTimeout(function () { showModal(el); }, 250);
    });
  }

  // ---------------- Search box (Shopee-style suggestions + history) ----------------
  function initSearchBoxes() {
    document.querySelectorAll('.ui-search').forEach(function (form) {
      var input = form.querySelector('.ui-search-in');
      var pop = form.querySelector('[data-role="pop"]');
      if (!input || !pop) return;

      var suggestUrl = form.dataset.suggestUrl || '';
      var clearUrl = form.dataset.clearUrl || '';
      var historySec = pop.querySelector('[data-role="history"]');
      var suggestSec = pop.querySelector('[data-role="suggest"]');
      var suggestList = pop.querySelector('[data-role="suggest-list"]');
      var historyList = pop.querySelector('[data-role="history-list"]');
      var clearBtn = pop.querySelector('[data-role="clear-history"]');
      var clearInput = form.querySelector('[data-search-clear]');
      var timer = null, fetchSeq = 0, activeRow = null;

      function escHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
          return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
      }
      function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
      }
      function searchUrl(term) {
        var u = new URL(form.getAttribute('action'), window.location.href);
        u.searchParams.set('q', term);
        return u.toString();
      }
      function visibleRows() {
        var out = [];
        pop.querySelectorAll('.ui-search-row').forEach(function (r) {
          if (r.offsetParent !== null) out.push(r);
        });
        return out;
      }
      function markActive(row) {
        if (activeRow && activeRow !== row) activeRow.classList.remove('is-active');
        if (row && !row.classList.contains('is-active')) row.classList.add('is-active');
        if (row) row.scrollIntoView({ block: 'nearest' });
        activeRow = row || null;
      }
      function closePop() {
        pop.hidden = true;
        if (activeRow) { activeRow.classList.remove('is-active'); activeRow = null; }
      }
      function syncClear() {
        if (clearInput) clearInput.hidden = input.value.trim() === '';
      }
      function openPop() { pop.hidden = false; }
      function showEmptyHistory() {
        if (!historyList) return;
        historyList.innerHTML = '<li class="ui-search-empty" data-role="history-empty">Belum ada pencarian.</li>';
      }
      function filterHistory(term) {
        if (!historySec || !historyList) return;
        var empty = historyList.querySelector('[data-role="history-empty"]');
        var shown = 0;
        historyList.querySelectorAll('li:not([data-role="history-empty"])').forEach(function (li) {
          var row = li.querySelector('.ui-search-row');
          var match = !row || li.textContent.toLowerCase().indexOf(term.toLowerCase()) !== -1;
          li.hidden = !match;
          if (!match && row) row.classList.remove('is-active');
          if (match) shown++;
        });
        if (empty) empty.hidden = true;
        historySec.hidden = shown === 0;
        if (shown === 0) activeRow = null;
      }
      function showAllHistory() {
        if (!historySec || !historyList) return;
        historyList.querySelectorAll('li').forEach(function (li) { li.hidden = false; });
        var empty = historyList.querySelector('[data-role="history-empty"]');
        if (empty) empty.hidden = false;
        historySec.hidden = false;
      }
      function clearSuggest() {
        suggestList.innerHTML = '';
        suggestSec.hidden = true;
      }
      function renderSuggest(term) {
        if (!suggestUrl || !term) return;
        var seq = ++fetchSeq;
        clearSuggest();
        suggestSec.hidden = false;

        var searchBtn = document.createElement('button');
        searchBtn.type = 'button';
        searchBtn.className = 'ui-search-row';
        searchBtn.dataset.url = searchUrl(term);
        searchBtn.innerHTML =
          '<span class="ms ui-search-row-ic" aria-hidden="true">search</span>' +
          '<span class="ui-search-row-tx">Cari &ldquo;' + escHtml(term) + '&rdquo;</span>' +
          '<span class="ms ui-search-row-go" aria-hidden="true">north_west</span>';
        suggestList.appendChild(searchBtn);

        var u = new URL(suggestUrl, window.location.href);
        u.searchParams.set('q', term);
        u.searchParams.set('limit', '8');

        fetch(u.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (seq !== fetchSeq) return;
            var seen = new Set([term.toLowerCase()]);
            (Array.isArray(data.results) ? data.results : []).forEach(function (it) {
              var key = String(it.text || '').toLowerCase();
              if (seen.has(key) || !it.url) return;
              seen.add(key);
              var node = document.createElement('button');
              node.type = 'button';
              node.className = 'ui-search-row';
              node.dataset.url = it.url;
              node.innerHTML =
                '<span class="ms ui-search-row-ic" aria-hidden="true">storefront</span>' +
                '<span class="ui-search-row-tx">' +
                  '<span class="ui-search-row-name">' + escHtml(it.text) + '</span>' +
                  (it.hint ? '<span class="ui-search-row-meta">' + escHtml(it.hint) + '</span>' : '') +
                '</span>' +
                '<span class="ms ui-search-row-go" aria-hidden="true">north_west</span>';
              suggestList.appendChild(node);
            });
          })
          .catch(function () {});
      }

      input.addEventListener('focus', function () {
        if (historySec || input.value.trim() !== '') openPop();
      });

      if (clearInput) {
        clearInput.addEventListener('click', function () {
          input.value = '';
          syncClear();
          input.focus();
        });
      }
      syncClear();

      input.addEventListener('input', function () {
        clearTimeout(timer);
        var term = input.value.trim();
        syncClear();
        openPop();
        if (term) {
          filterHistory(term);
          timer = setTimeout(function () { renderSuggest(term); }, 220);
        } else {
          clearSuggest();
          showAllHistory();
        }
      });

      input.addEventListener('keydown', function (e) {
        var key = e.key;
        if (key !== 'ArrowDown' && key !== 'ArrowUp' && key !== 'Enter' && key !== 'Escape') return;
        if (key === 'ArrowDown' || key === 'ArrowUp') {
          e.preventDefault();
          var rows = visibleRows();
          if (!rows.length) return;
          var idx = rows.indexOf(activeRow);
          idx = key === 'ArrowDown' ? Math.min(rows.length - 1, idx + 1) : Math.max(0, idx - 1);
          markActive(rows[idx]);
        } else if (key === 'Enter') {
          if (activeRow) {
            e.preventDefault();
            window.location.href = activeRow.dataset.url;
          }
        } else if (key === 'Escape') {
          closePop();
        }
      });

      pop.addEventListener('click', function (e) {
        var row = e.target.closest('.ui-search-row');
        if (row) window.location.href = row.dataset.url;
      });

      document.addEventListener('click', function (e) {
        if (!form.contains(e.target)) closePop();
      });

      if (clearBtn && clearUrl) {
        clearBtn.addEventListener('click', function () {
          fetch(clearUrl, {
            method: 'DELETE',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf(),
              'X-Requested-With': 'XMLHttpRequest',
            },
          })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function () {
              clearBtn.hidden = true;
              historySec.hidden = true;
              showEmptyHistory();
            })
            .catch(function () {});
        });
      }
    });
  }

  // ---------------- Nickname check (validasi ID game) ----------------
  // Markup: <div data-nickname-check data-endpoint="..." data-brand="...">
  //   <input data-nickname-target> [<input data-nickname-zone>]
  //   <div data-nickname-result> ... </div>
  // </div>
  function initNicknameChecks() {
    function esc(s) {
      return String(s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }

    document.querySelectorAll('[data-nickname-check]').forEach(function (root) {
      var endpoint = root.dataset.endpoint;
      var brand = root.dataset.brand;
      var targetInput = root.querySelector('[data-nickname-target]');
      var zoneInput = root.querySelector('[data-nickname-zone]');
      var result = root.querySelector('[data-nickname-result]');
      var buyBtn = document.querySelector('[data-nickname-buy]');
      if (!endpoint || !brand || !targetInput || !result) return;

      var timer = null;
      var seq = 0;
      var valid = false;

      function setBuyEnabled(enabled) {
        if (!buyBtn) return;
        buyBtn.disabled = !enabled;
        if (enabled) {
          buyBtn.classList.remove('opacity-50');
        } else {
          buyBtn.classList.add('opacity-50');
        }
      }

      function setResult(html) {
        result.innerHTML = html;
      }

      function message(text, tone) {
        valid = false;
        setBuyEnabled(false);
        setResult('<span class="' + (tone || 'tx-skyd') + '" style="opacity:.85">' + esc(text) + '</span>');
      }

      function run() {
        var target = targetInput.value.trim();
        var zone = zoneInput ? zoneInput.value.trim() : '';
        var id = ++seq;

        if (target === '') {
          valid = false;
          setBuyEnabled(false);
          message('Masukkan ID untuk melihat nama akun.');
          return;
        }
        if (zoneInput && zone === '') {
          valid = false;
          setBuyEnabled(false);
          message('Masukkan Zone ID untuk melanjutkan.', 'tx-err');
          return;
        }

        message('Memeriksa...', 'tx-mute');

        var url = new URL(endpoint, window.location.href);
        url.searchParams.set('brand', brand);
        url.searchParams.set('target', target);
        if (zone) url.searchParams.set('zone', zone);

        fetch(url.toString(), {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (id !== seq) return;
            if (data && data.status && data.nickname) {
              valid = true;
              setBuyEnabled(true);
              setResult('<span class="tx-mintt fw-bold"><span class="ms ms-sm align-middle me-1">verified</span>' + esc(data.nickname) + '</span>');
            } else {
              valid = false;
              setBuyEnabled(false);
              message((data && data.message) || 'Nama akun tidak ditemukan.', 'tx-err');
            }
          })
          .catch(function () {
            if (id === seq) {
              valid = false;
              setBuyEnabled(false);
              message('Gagal memeriksa, silakan coba lagi.', 'tx-err');
            }
          });
      }

      function schedule() {
        clearTimeout(timer);
        timer = setTimeout(run, 450);
      }

      targetInput.addEventListener('input', schedule);
      targetInput.addEventListener('blur', run);
      if (zoneInput) {
        zoneInput.addEventListener('input', schedule);
        zoneInput.addEventListener('blur', run);
      }
      var form = root.closest('form[data-nickname-form]');
      if (form) {
        form.addEventListener('submit', function (e) {
          if (!valid) {
            e.preventDefault();
            if (window.AP && window.AP.showToast) {
              window.AP.showToast('ID/Zone belum terverifikasi atau belum lengkap.', 'error');
            } else {
              alert('ID/Zone belum terverifikasi atau belum lengkap.');
            }
            if (!targetInput.value.trim()) targetInput.focus();
            else if (zoneInput && !zoneInput.value.trim()) zoneInput.focus();
            run();
            return;
          }
        });
      }
      setBuyEnabled(false);
    });
  }


  // ---------------- Phone operator detection for pulsa/data ----------------
  function initOperatorChecks() {
    document.querySelectorAll('[data-operator-check]').forEach(function (root) {
      var endpoint = root.dataset.endpoint;
      var numberInput = root.querySelector('[data-operator-number]');
      var submitBtn = root.querySelector('[data-operator-submit]');
      var resultEl = root.querySelector('[data-operator-result]');
      var ajaxRoot = root.closest('[data-ajax]');
      if (!endpoint || !numberInput || !resultEl) return;

      var timer = null;
      var seq = 0;

      function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
          return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
      }

      function setResult(html) {
        resultEl.innerHTML = html;
      }

      function message(text, tone) {
        setResult('<span class="' + (tone || 'tx-skyd') + '" style="opacity:.85">' + esc(text) + '</span>');
      }

      function setSelectValue(el, value, text) {
        if (!el || value == null || value === '') return;
        value = String(value);
        if (el.tagName !== 'SELECT') { el.value = value; return; }
        if (el.tomselect) {
          if (!el.tomselect.options[value]) {
            var opt = el.querySelector('option[value="' + value + '"]');
            if (!opt) {
              opt = document.createElement('option');
              opt.value = value;
              opt.textContent = text || value;
              el.appendChild(opt);
            }
            el.tomselect.addOption({ value: value, text: opt.textContent });
          }
          el.tomselect.setValue(value, true);
        } else {
          var plain = el.querySelector('option[value="' + value + '"]');
          if (!plain) {
            plain = document.createElement('option');
            plain.value = value;
            plain.textContent = text || value;
            el.appendChild(plain);
          }
          el.value = value;
        }
      }

      function applyFilter(detected) {
        if (!ajaxRoot) return;
        var form = ajaxRoot.querySelector('[data-ajax-form]');
        if (!form) return;

        var brandInput = form.elements['brand'];
        var categoryInput = form.elements['category'];

        // Kategori hanya diisi bila belum ada (halaman biasanya sudah membawa category).
        if (categoryInput && !categoryInput.value) {
          var cats = (ajaxRoot.dataset.operatorCategories || '').split(',').filter(Boolean);
          var catVal = detected.category_id || cats[0] || '';
          if (catVal) {
            setSelectValue(categoryInput, catVal, detected.category_name || '');
          }
        }

        if (brandInput) {
          setSelectValue(brandInput, detected.brand_id, detected.brand_name || detected.operator);
        }

        if (typeof ajaxRoot._load === 'function') {
          ajaxRoot._load(1);
        }
        ajaxRoot.classList.add('is-filtered');
        setTimeout(function () {
          var rows = ajaxRoot.querySelector('[data-ajax-rows]');
          if (rows) rows.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 150);
      }

      function run() {
        var number = numberInput.value.trim();
        var id = ++seq;
        if (number === '') {
          message('Masukkan nomor HP untuk menampilkan paket.');
          return;
        }
        message('Memeriksa nomor...', 'tx-mute');

        var url = new URL(endpoint, window.location.href);
        url.searchParams.set('number', number);

        fetch(url.toString(), {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (id !== seq) return;
            if (data && data.status && data.brand_id) {
              var cats = (ajaxRoot && ajaxRoot.dataset.operatorCategories || '').split(',').filter(Boolean);
              var detectedCat = cats[0] || '';
              var logo = data.brand_logo
                ? '<img src="' + esc(data.brand_logo) + '" alt="" class="brand-logo-img brand-logo-sm me-1">'
                : '<span class="ms ms-sm align-middle me-1">verified</span>';
              setResult('<span class="tx-mintt fw-bold d-inline-flex align-items-center">' + logo + 'Operator: ' + esc(data.operator) + '</span>');
              applyFilter({ brand_id: data.brand_id, brand_name: data.brand_name, operator: data.operator, category_id: detectedCat });
            } else {
              message((data && data.message) || 'Nomor tidak dikenali.', 'tx-err');
            }
          })
          .catch(function () {
            if (id === seq) message('Gagal memeriksa nomor, silakan coba lagi.', 'tx-err');
          });
      }

      function schedule() {
        clearTimeout(timer);
        timer = setTimeout(run, 400);
      }

      numberInput.addEventListener('input', schedule);
      numberInput.addEventListener('blur', run);
      if (submitBtn) {
        submitBtn.addEventListener('click', function (e) {
          e.preventDefault();
          run();
        });
      }
      numberInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          run();
        }
      });
    });
  }


  // ---------------- Loading overlay ----------------
  function initLoader() {
    var el = document.querySelector('[data-loader]');
    if (!el) return;
    var timeEl = el.querySelector('[data-loader-time]');
    var startedAt = 0, timer = null, hideTimer = null;

    function tick() {
      if (timeEl) timeEl.textContent = ((Date.now() - startedAt) / 1000).toFixed(1) + 's';
    }
    function show() {
      clearTimeout(hideTimer);
      el.hidden = false;
      requestAnimationFrame(function () { el.classList.add('is-visible'); });
      startedAt = Date.now();
      tick();
      clearInterval(timer);
      timer = setInterval(tick, 100);
    }
    function hide() {
      clearInterval(timer);
      timer = null;
      el.classList.remove('is-visible');
      hideTimer = setTimeout(function () {
        if (!el.classList.contains('is-visible')) el.hidden = true;
      }, 180);
    }

    window.AP = window.AP || {};
    window.AP.showLoader = show;
    window.AP.hideLoader = hide;

    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!(form instanceof HTMLFormElement)) return;
      if ((form.getAttribute('method') || 'get').toLowerCase() === 'get') return;
      if (form.hasAttribute('data-no-loader') || form.dataset.loader === 'off' || form.hasAttribute('data-min-loader')) return;
      if (form.target && form.target !== '' && form.target !== '_self') return;
      show();
      setTimeout(function () { if (e.defaultPrevented) hide(); }, 0);
    }, true);

    window.addEventListener('pageshow', function (e) { if (e.persisted) hide(); });
  }

  // ---------------- Toast notifications ----------------
  // Markup: tidak perlu — container dibuat otomatis oleh showToast().
  function showToast(message, type) {
    if (!message) return;
    var container = document.querySelector('.ap-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'ap-toast-container';
      document.body.appendChild(container);
    }
    var toast = document.createElement('div');
    toast.className = 'ap-toast ap-toast-' + (type || 'info');
    toast.setAttribute('role', 'status');
    toast.textContent = message;
    container.appendChild(toast);
    requestAnimationFrame(function () { toast.classList.add('show'); });
    setTimeout(function () {
      toast.classList.remove('show');
      setTimeout(function () { toast.remove(); }, 250);
    }, 3500);
  }

  window.AP = {
    openModal: openModal,
    closeModal: function (id) { closeModal(typeof id === 'string' ? document.getElementById(id) : id); },
    confirm: confirmAction,
    showToast: showToast,
    initSelects: function (scope) {
      initSearchSelects(scope);
      initMultiSelects(scope);
    },
  };

  // ---------------- Payment methods (checkout) ----------------
  // Markup: <div data-payment-methods>
  //   <input type="radio" name="payment_method" ...>
  //   <div data-payment-channel="va ewallet">...</div>
  // </div>
  function initPaymentMethods() {
    document.querySelectorAll('[data-payment-methods]').forEach(function (root) {
      var inputs = root.querySelectorAll('input[name="payment_method"]');
      var blocks = root.querySelectorAll('[data-payment-channel]');
      if (!inputs.length) return;

      function sync() {
        var checked = root.querySelector('input[name="payment_method"]:checked');
        var value = checked ? checked.value : '';
        blocks.forEach(function (block) {
          var allowed = (block.dataset.paymentChannel || '').split(/\s+/);
          var on = value !== '' && allowed.indexOf(value) > -1;
          block.classList.toggle('d-none', !on);
          var field = block.querySelector('select, input, textarea');
          if (field) field.disabled = !on;
        });

        var saldoWrap = document.querySelector('[data-checkout-saldo]');
        if (saldoWrap) {
          if (value === 'saldo') {
            saldoWrap.classList.remove('d-none');
            saldoWrap.classList.add('d-flex');
          } else {
            saldoWrap.classList.add('d-none');
            saldoWrap.classList.remove('d-flex');
          }
        }
      }

      inputs.forEach(function (input) { input.addEventListener('change', sync); });
      sync();
    });
  }

  // Form ber-atribut data-min-loader="2000" dikirim lewat fetch supaya overlay
  // loader tampil minimal sekian ms — atau lebih lama bila proses backend lebih
  // lambat — baru halaman diarahkan ke hasilnya. Redirect sukses maupun error
  // validasi tetap ditangani (halaman tujuan dimuat via location.assign).
  function initMinLoaderForms() {
    document.querySelectorAll('form[data-min-loader]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (form.dataset.submitting === '1') return;
        e.preventDefault();
        form.dataset.submitting = '1';

        var isCheckout = form.hasAttribute('data-checkout-form') || (form.action && form.action.indexOf('/checkout/') !== -1);
        if (isCheckout) {
          var targetInput = form.querySelector('input[name="target"]');
          var zoneInput = form.querySelector('input[name="zone"]');
          var requiresZone = form.dataset.requiresZone === '1';
          if (targetInput) {
            var targetVal = (targetInput.value || '').trim();
            if (targetVal === '') {
              if (window.AP && window.AP.showToast) {
                window.AP.showToast('ID Tujuan wajib diisi.', 'error');
              } else {
                alert('ID Tujuan wajib diisi.');
              }
              form.dataset.submitting = '';
              var btns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
              btns.forEach(function (b) { b.disabled = false; });
              targetInput.focus();
              targetInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
              return;
            }
          }
          if (requiresZone && zoneInput) {
            var zoneVal = (zoneInput.value || '').trim();
            if (zoneVal === '') {
              if (window.AP && window.AP.showToast) {
                window.AP.showToast('Zone ID wajib diisi.', 'error');
              } else {
                alert('Zone ID wajib diisi.');
              }
              form.dataset.submitting = '';
              var btns2 = form.querySelectorAll('button[type="submit"], input[type="submit"]');
              btns2.forEach(function (b) { b.disabled = false; });
              zoneInput.focus();
              zoneInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
              return;
            }
          } else if (zoneInput && zoneInput.required) {
            var zoneVal2 = zoneInput.value.trim();
            if (zoneVal2 === '') {
              if (window.AP && window.AP.showToast) {
                window.AP.showToast('Zone wajib diisi.', 'error');
              } else {
                alert('Zone wajib diisi.');
              }
              form.dataset.submitting = '';
              var btns2b = form.querySelectorAll('button[type="submit"], input[type="submit"]');
              btns2b.forEach(function (b) { b.disabled = false; });
              if (zoneInput) { zoneInput.focus(); zoneInput.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
              return;
            }
          }
          var methodChk = form.querySelector('input[name="payment_method"]:checked');
          var methodChkVal = methodChk ? methodChk.value : '';
          if (methodChkVal === 'saldo') {
            var sisa = document.querySelector('[data-sisa-saldo]');
            if (sisa) {
              var stRaw = sisa.textContent || '';
              if (stRaw.indexOf('-') !== -1 || stRaw.match(/Rp\s*-/)) {
                if (window.AP && window.AP.showToast) {
                  window.AP.showToast('Saldo tidak mencukupi.', 'error');
                } else {
                  alert('Saldo tidak mencukupi.');
                }
                form.dataset.submitting = '';
                var btns3 = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                btns3.forEach(function (b) { b.disabled = false; });
                return;
              }
              var st = stRaw.replace(/\D/g, '');
              if (st !== '' && parseInt(st, 10) < 0) {
                if (window.AP && window.AP.showToast) {
                  window.AP.showToast('Saldo tidak mencukupi.', 'error');
                } else {
                  alert('Saldo tidak mencukupi.');
                }
                form.dataset.submitting = '';
                var btns3 = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                btns3.forEach(function (b) { b.disabled = false; });
                return;
              }
            } else {
              var allText = document.body.innerText || '';
              if (/Sisa setelah transaksi/.test(allText) && /Rp\s*-\s*[0-9.,]+/.test(allText)) {
                if (window.AP && window.AP.showToast) {
                  window.AP.showToast('Saldo tidak mencukupi.', 'error');
                } else {
                  alert('Saldo tidak mencukupi.');
                }
                form.dataset.submitting = '';
                var btns3b = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                btns3b.forEach(function (b) { b.disabled = false; });
                return;
              }
            }
          }
        }
        var methodInput = form.querySelector('input[name="payment_method"]:checked');
        var methodVal = methodInput ? methodInput.value : '';
        var msg = isCheckout ? 'Apakah pesanan dilanjutkan?' : 'Apakah transaksi ini dilanjutkan?';
        var title = isCheckout ? 'Konfirmasi Pesanan' : 'Konfirmasi Transaksi';
        if (window.AP && typeof window.AP.confirm === 'function') {
          window.AP.confirm({ message: msg, title: title, okLabel: 'Lanjutkan' }, function (ok) {
            if (!ok) {
              form.dataset.submitting = '';
              var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
              buttons.forEach(function (b) { b.disabled = false; });
              return;
            }
            // Re-check saldo saat benar-benar mau lanjut
            var methodChk2 = form.querySelector('input[name="payment_method"]:checked');
            var methodChkVal2 = methodChk2 ? methodChk2.value : '';
            if (methodChkVal2 === 'saldo') {
              var sisa2 = document.querySelector('[data-sisa-saldo]');
              if (sisa2) {
                var stRaw2 = sisa2.textContent || '';
                if (stRaw2.indexOf('-') !== -1) {
                  if (window.AP && window.AP.showToast) {
                    window.AP.showToast('Saldo tidak mencukupi. Silakan pilih metode QRIS untuk melanjutkan.', 'error');
                  } else {
                    alert('Saldo tidak mencukupi. Silakan pilih metode QRIS untuk melanjutkan.');
                  }
                  form.dataset.submitting = '';
                  var buttonsBad = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                  buttonsBad.forEach(function (b) { b.disabled = false; });
                  return;
                }
              }
            }
            var min = parseInt(form.dataset.minLoader, 10);
            if (isNaN(min) || min < 0) min = 2000;
            var started = Date.now();
            var data = new FormData(form);

              var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
              buttons.forEach(function (b) { b.disabled = true; });

              if (window.AP && AP.showLoader) AP.showLoader();

            fetch(form.action, {
              method: (form.getAttribute('method') || 'post').toUpperCase(),
              body: data,
              credentials: 'same-origin',
              headers: { 'Accept': 'application/json, text/html;q=0.9', 'X-Requested-With': 'XMLHttpRequest' },
            }).then(function (response) {
              if (!response.ok && response.status >= 400 && response.status < 500) {
                return response.text().then(function (html) {
                  if (window.AP && AP.hideLoader) AP.hideLoader();
                  form.dataset.submitting = '';
                  var buttonsErr = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                  buttonsErr.forEach(function (b) { b.disabled = false; });
                  var msg = 'Ada transaksi QRIS yang belum dibayar untuk produk & tujuan yang sama. Selesaikan atau tunggu kedaluwarsa.';
                  try {
                    // try json
                    try {
                      var j = JSON.parse(html);
                      if (j && j.message) msg = j.message;
                      else if (j && j.errors) {
                        var keys = Object.keys(j.errors);
                        if (keys.length) msg = Array.isArray(j.errors[keys[0]]) ? j.errors[keys[0]][0] : j.errors[keys[0]];
                      }
                    } catch(e) {
                      var doc = new DOMParser().parseFromString(html, 'text/html');
                      var err = doc.querySelector('.err.show, .alert-ap, .text-danger, .tx-err, .invalid-feedback, .alert-danger');
                      if (err && err.textContent.trim()) {
                        msg = err.textContent.trim();
                      }
                    }
                  } catch (e) {}
                  if (typeof confirmAction === 'function') {
                    confirmAction({ title: 'Tidak bisa melanjutkan', message: msg, okLabel: 'Mengerti' }, function(){});
                  } else if (window.AP && window.AP.showToast) {
                    window.AP.showToast(msg, 'error');
                  } else {
                    alert(msg);
                  }
                  throw new Error('validation');
                });
              }
              if (response.redirected && response.url) {
                return fetch(response.url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                  .then(function(r){ return r.text(); })
                  .then(function(html){
                    try {
                      var doc = new DOMParser().parseFromString(html, 'text/html');
                      var err = doc.querySelector('.err.show, .alert-ap, .text-danger, .tx-err, .invalid-feedback, .alert-danger');
                      if (err && err.textContent.trim()) {
                        if (window.AP && AP.hideLoader) AP.hideLoader();
                        form.dataset.submitting = '';
                        var buttonsErr = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                        buttonsErr.forEach(function (b) { b.disabled = false; });
                        var msg2 = err.textContent.trim();
                        if (typeof confirmAction === 'function') {
                          confirmAction({ title: 'Tidak bisa melanjutkan', message: msg2, okLabel: 'Mengerti' }, function(){});
                        } else if (window.AP && window.AP.showToast) {
                          window.AP.showToast(msg2, 'error');
                        } else {
                          alert(msg2);
                        }
                        throw new Error('validation');
                      }
                    } catch(e){}
                    var wait = Math.max(0, min - (Date.now() - started));
                    setTimeout(function () { window.location.assign(response.url); }, wait);
                  });
              }
              var wait = Math.max(0, min - (Date.now() - started));
              setTimeout(function () {
                if (response.redirected && response.url) {
                  window.location.assign(response.url);
                } else {
                  window.location.reload();
                }
              }, wait);
            }).catch(function (err) {
              if (err && err.message === 'validation') return;
              if (window.AP && AP.hideLoader) AP.hideLoader();
              form.dataset.submitting = '';
              var buttons2f = form.querySelectorAll('button[type="submit"], input[type="submit"]');
              buttons2f.forEach(function (b) { b.disabled = false; });
            });
          });
          return;
        }

        var min2 = parseInt(form.dataset.minLoader, 10);
        if (isNaN(min2) || min2 < 0) min2 = 2000;
        var started2 = Date.now();
        var data2 = new FormData(form);

        var buttons2f = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        buttons2f.forEach(function (b) { b.disabled = true; });

        if (window.AP && AP.showLoader) AP.showLoader();

        fetch(form.action, {
          method: (form.getAttribute('method') || 'post').toUpperCase(),
          body: data2,
          credentials: 'same-origin',
          headers: { 'Accept': 'text/html' },
        }).then(function (response) {
          var wait = Math.max(0, min2 - (Date.now() - started2));
          setTimeout(function () {
            if (response.redirected && response.url) {
              window.location.assign(response.url);
            } else {
              // Show validation message if present
              if (window.AP && AP.showToast) {
                try {
                  // best effort: just reload
                } catch (e) {}
              }
              window.location.reload();
            }
          }, wait);
        }).catch(function () {
          if (window.AP && AP.hideLoader) AP.hideLoader();
          form.dataset.submitting = '';
          buttons2f.forEach(function (b) { b.disabled = false; });
          form.submit();
        });
      });
    });
  }

  // ---------------- Salin ke clipboard ----------------
  // <button data-copy="100000" data-copy-label="Nominal">Salin</button>
  // menyalin nilai data-copy ke clipboard dan menampilkan toast.
  function initCopy() {
    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('[data-copy]');
      if (!trigger) return;
      e.preventDefault();

      var value = trigger.getAttribute('data-copy');
      if (!value) return;

      var label = trigger.getAttribute('data-copy-label') || 'Teks';

      function done(ok) {
        if (window.AP && window.AP.showToast) {
          window.AP.showToast(
            ok ? label + ' disalin.' : 'Gagal menyalin. Salin manual ya.',
            ok ? 'success' : 'error'
          );
        }
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(value).then(function () { done(true); }).catch(function () { done(false); });
        return;
      }

      var helper = document.createElement('textarea');
      helper.value = value;
      helper.setAttribute('readonly', '');
      helper.style.position = 'fixed';
      helper.style.top = '-1000px';
      helper.style.opacity = '0';
      document.body.appendChild(helper);
      helper.select();
      var ok = false;
      try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
      document.body.removeChild(helper);
      done(ok);
    });
  }

  // ---------------- Preset nominal ----------------
  // Tombol <button data-amount-set="20000" data-amount-target="input[name=amount]">
  // mengisi field nominal terkait saat diklik.
  function initAmountPresets() {
    document.querySelectorAll('[data-amount-set]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var selector = btn.dataset.amountTarget || 'input[name="amount"]';
        var field = document.querySelector(selector);
        if (!field) return;
        field.value = btn.dataset.amountSet;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.focus();
      });
    });
  }

  // ---------------- Currency input ----------------
  // <div data-currency> berisi [data-currency-display] (tampilan ber-titik)
  // dan [data-currency-value] (nilai mentah yang dikirim ke server).
  function initCurrencyInputs() {
    document.querySelectorAll('[data-currency]').forEach(function (wrap) {
      var display = wrap.querySelector('[data-currency-display]');
      var hidden = wrap.querySelector('[data-currency-value]');
      if (!display) return;

      function group(digits) {
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      }

      function sync() {
        var raw = display.value;
        var caret = typeof display.selectionStart === 'number' ? display.selectionStart : raw.length;
        var digitsBefore = raw.slice(0, caret).replace(/\D/g, '').length;
        var digits = raw.replace(/\D/g, '');
        var formatted = group(digits);
        display.value = formatted;
        if (hidden) hidden.value = digits;

        var pos = 0, seen = 0;
        while (pos < formatted.length && seen < digitsBefore) {
          var code = formatted.charCodeAt(pos);
          if (code >= 48 && code <= 57) seen++;
          pos++;
        }
        if (typeof display.setSelectionRange === 'function') display.setSelectionRange(pos, pos);
      }

      display.addEventListener('input', sync);
      sync();
    });
  }

  // ---------------- Checkout target hand-off (pulsa/data & game) ----------------
  // Kartu paket di halaman pulsa/game menaut ke checkout; tujuan yang sudah
  // diisi di kolom deteksi (nomor HP operator, atau ID + Zone nickname game)
  // dibawa serta ke halaman checkout.
  function initCheckoutTargets() {
    document.addEventListener('click', function (e) {
      var link = e.target.closest('[data-checkout-link]');
      if (!link) return;

      var operatorRoot = document.querySelector('[data-operator-check]');
      var nicknameRoot = document.querySelector('[data-nickname-check]');
      var root = operatorRoot || nicknameRoot;
      if (!root) return;

      var number = '';
      var zone = '';

      if (root === operatorRoot) {
        var input = root.querySelector('[data-operator-number]');
        number = input ? input.value.trim() : '';
      } else {
        var targetInput = root.querySelector('[data-nickname-target]');
        var zoneInput = root.querySelector('[data-nickname-zone]');
        number = targetInput ? targetInput.value.trim() : '';
        zone = zoneInput ? zoneInput.value.trim() : '';
      }

      var base = link.getAttribute('data-checkout-base') || link.getAttribute('href');
      if (!base) return;

      e.preventDefault();

      if (number === '') {
        var focusEl = root === operatorRoot
          ? root.querySelector('[data-operator-number]')
          : root.querySelector('[data-nickname-target]');
        if (focusEl) {
          focusEl.focus();
          focusEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        var resultEl = root.querySelector('[data-operator-result], [data-nickname-result]');
        if (resultEl) {
          resultEl.innerHTML = '<span class="tx-err fw-bold">Isi Tujuan dulu ya.</span>';
        }
        return;
      }

      var url = new URL(base, window.location.origin);
      url.searchParams.set('target', number);
      if (zone) url.searchParams.set('zone', zone);
      window.location.href = url.toString();
    });
  }

  // ---------------- Clickable rows ----------------
  // Baris list (mis. riwayat transaksi) membawa data-row-href; klik di area
  // mana pun pada baris membuka tautan, kecuali pada elemen interaktif.
  function initRowLinks() {
    document.addEventListener('click', function (e) {
      var row = e.target.closest('[data-row-href]');
      if (!row) return;
      if (e.target.closest('a,button,input,select,textarea,label')) return;
      var href = row.getAttribute('data-row-href');
      if (href) window.location.href = href;
    });
  }

  // ---------------- Password reveal ----------------
  // Tombol mata pada <x-ui.input type="password"> via data-toggle-password.
  function initPasswordToggles() {
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var input = document.getElementById(btn.getAttribute('data-toggle-password'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        var icon = btn.firstElementChild;
        if (icon) icon.textContent = show ? 'visibility_off' : 'visibility';
      });
    });
  }

  // ---------------- Auto-refresh status (polling) ----------------
  // Elemen dengan [data-poll-url] dicek berkala. Server membalas JSON
  // { "status": "..." }. Halaman dimuat ulang begitu status berbeda dari
  // data-poll-status yang dirender (mis. pending -> processing -> success).
  function initPollers() {
    document.querySelectorAll('[data-poll-url]').forEach(function (el) {
      var url = el.getAttribute('data-poll-url');
      if (!url) return;
      var initialStatus = el.getAttribute('data-poll-status');
      var interval = parseInt(el.getAttribute('data-poll-interval'), 10) || 5000;
      var timer = null;
      var stopped = false;

      function stop() {
        if (timer) {
          clearInterval(timer);
          timer = null;
        }
      }

      function tick() {
        if (stopped || document.hidden) return;
        fetch(url, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data || typeof data.status === 'undefined') return;
            if (initialStatus !== null && data.status !== initialStatus) {
              stopped = true;
              stop();
              window.location.reload();
            }
          })
          .catch(function () {});
      }

      function start() {
        stop();
        if (stopped) return;
        timer = setInterval(tick, interval);
      }

      start();

      document.addEventListener('visibilitychange', function () {
        if (document.hidden) stop();
        else start();
      });
    });
  }

  // Breadcrumb: tandai item yang kepanjangan lalu jalankan teks berjalan saat hover.
  function initBreadcrumbs() {
    document.querySelectorAll('.crumb-label').forEach(function (label) {
      var text = label.querySelector('.crumb-label-text');
      if (!text) return;

      var check = function () {
        var shift = text.scrollWidth - label.clientWidth;
        if (shift > 1) {
          label.classList.add('is-truncated');
          label.style.setProperty('--crumb-shift', shift + 'px');
        } else {
          label.classList.remove('is-truncated');
          label.style.removeProperty('--crumb-shift');
        }
      };

      check();
      if (window.ResizeObserver) new ResizeObserver(check).observe(label);
    });
  }

  // ---------------- Admin topbar search (lintas modul) ----------------
  function initAdminSearch() {
    document.querySelectorAll('[data-admin-search]').forEach(function (root) {
      var input = root.querySelector('.admin-search-input');
      var pop = root.querySelector('[data-admin-search-pop]');
      var url = root.dataset.searchUrl || '';
      if (!input || !pop || !url) return;

      var timer = null, seq = 0, activeRow = null;

      function escHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
          return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
      }
      function closePop() {
        pop.hidden = true;
        if (activeRow) { activeRow.classList.remove('is-active'); activeRow = null; }
      }
      function openPop() { pop.hidden = false; }
      function visibleRows() {
        return Array.prototype.filter.call(pop.querySelectorAll('.ui-search-row'), function (r) {
          return r.offsetParent !== null;
        });
      }
      function markActive(row) {
        if (activeRow && activeRow !== row) activeRow.classList.remove('is-active');
        if (row && !row.classList.contains('is-active')) row.classList.add('is-active');
        if (row) row.scrollIntoView({ block: 'nearest' });
        activeRow = row || null;
      }
      function render(term) {
        var s = ++seq;
        fetch(url + '?q=' + encodeURIComponent(term), {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (s !== seq) return;
            var groups = (data && Array.isArray(data.groups)) ? data.groups : [];
            var html = '';
            groups.forEach(function (g) {
              if (!g.items || !g.items.length) return;
              html += '<div class="ui-search-sec"><div class="ui-search-head">' + escHtml(g.label) + '</div><ul class="ui-search-list">';
              g.items.forEach(function (it) {
                html += '<li><button type="button" class="ui-search-row" data-url="' + escHtml(it.url) + '">' +
                  '<span class="ms ui-search-row-ic" aria-hidden="true">' + escHtml(it.icon || 'search') + '</span>' +
                  '<span class="ui-search-row-tx">' +
                    '<span class="ui-search-row-name">' + escHtml(it.text) + '</span>' +
                    (it.hint ? '<span class="ui-search-row-meta">' + escHtml(it.hint) + '</span>' : '') +
                  '</span></button></li>';
              });
              html += '</ul></div>';
            });
            pop.innerHTML = html || '<div class="ui-search-empty">Tidak ada hasil.</div>';
            openPop();
          })
          .catch(function () {});
      }

      input.addEventListener('input', function () {
        clearTimeout(timer);
        var term = input.value.trim();
        if (term.length < 2) { closePop(); pop.innerHTML = ''; return; }
        timer = setTimeout(function () { render(term); }, 220);
      });

      input.addEventListener('focus', function () {
        if (pop.innerHTML.trim() !== '') openPop();
      });

      input.addEventListener('keydown', function (e) {
        var key = e.key;
        if (key !== 'ArrowDown' && key !== 'ArrowUp' && key !== 'Enter' && key !== 'Escape') return;
        if (key === 'ArrowDown' || key === 'ArrowUp') {
          e.preventDefault();
          var rows = visibleRows();
          if (!rows.length) return;
          var idx = rows.indexOf(activeRow);
          idx = key === 'ArrowDown' ? Math.min(rows.length - 1, idx + 1) : Math.max(0, idx - 1);
          markActive(rows[idx]);
        } else if (key === 'Enter') {
          if (activeRow) { e.preventDefault(); window.location.href = activeRow.dataset.url; }
        } else if (key === 'Escape') {
          closePop();
        }
      });

      pop.addEventListener('click', function (e) {
        var row = e.target.closest('.ui-search-row');
        if (row) window.location.href = row.dataset.url;
      });

      document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) closePop();
      });
    });
  }

  // ---------------- Pusat notifikasi (lonceng topbar) ----------------
  function initNotifications() {
    document.querySelectorAll('[data-notif]').forEach(function (root) {
      var toggle = root.querySelector('[data-notif-toggle]');
      var pop = root.querySelector('[data-notif-pop]');
      var list = root.querySelector('[data-notif-list]');
      var url = root.dataset.notifUrl || '';
      console.log('notif url', url, root);
      if (!toggle || !pop || !list || !url) return;

      var interval = parseInt(root.dataset.notifInterval, 10) || 15000;
      var storageKey = 'ap_notif_last_seen:' + (root.dataset.notifUser || 'guest');
      var listLoaded = false;
      var inflight = false;
      var timer = null;

      function request() {
        return fetch(url, {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          cache: 'no-store',
        }).then(function (r) { return r.json(); });
      }

      function readLastSeen() {
        try { return localStorage.getItem(storageKey); } catch (e) { return null; }
      }
      function writeLastSeen(id) {
        try { localStorage.setItem(storageKey, id || ''); } catch (e) { /* ignore */ }
      }

      function updDot(data) {
        var count = data.unread || 0;
        var display = count > 99 ? '99+' : count.toString();
        document.querySelectorAll('[data-notif-dot]').forEach(function (d) {
          if (count > 0) {
            d.textContent = display;
            d.hidden = false;
          } else {
            d.hidden = true;
          }
        });
      }

      function renderList(data) {
        list.innerHTML = data.html || '';
        listLoaded = true;
      }

      // Sinkronkan badge + toast notifikasi baru (dibandingkan dengan yang
      // terakhir dilihat, tersimpan di localStorage agar tetap muncul walau
      // ada reload/redirect — mis. setelah top up atau pembelian).
      function apply(data, announce) {
        updDot(data);
        var incoming = data.latest_id || null;
        var lastSeen = readLastSeen();
        var hasBaseline = lastSeen !== null;

        if (announce && incoming !== null && incoming !== lastSeen && data.latest_unread) {
          if (window.AP && window.AP.showToast) {
            var message = data.latest_body || data.latest_title;
            if (message) {
              window.AP.showToast(message, data.latest_level || 'info');
            }
          }
          if (!pop.hidden) renderList(data);
        }

        if (incoming !== null || !hasBaseline) writeLastSeen(incoming);
      }

      function poll() {
        if (inflight || document.hidden) return;
        inflight = true;
        request()
          .then(function (data) { apply(data, true); })
          .catch(function () {})
          .finally(function () { inflight = false; });
      }

      function loadList() {
        if (inflight) return;
        inflight = true;
        request()
          .then(function (data) {
            updDot(data);
            renderList(data);
          })
          .catch(function () {})
          .finally(function () { inflight = false; });
      }

      function open() {
        pop.hidden = false;
        if (!listLoaded) loadList();
      }
      function close() { pop.hidden = true; }

      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        if (pop.hidden) open(); else close();
      });

      document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) close();
      });

      root.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
      });

      // Panggilan awal: toast bila ada notif baru sejak kunjungan terakhir.
      poll();

      function start() {
        stop();
        timer = setInterval(poll, interval);
      }
      function stop() {
        if (timer) {
          clearInterval(timer);
          timer = null;
        }
      }

      start();

      document.addEventListener('visibilitychange', function () {
        if (document.hidden) stop();
        else {
          poll();
          start();
        }
      });
    });
  }

  function initPushToggle() {
    document.querySelectorAll('[data-push-toggle]').forEach(function (btn) {
      var status = document.querySelector('[data-push-status]');
      var push = window.ATL_Push;

      function render() {
        var on = push && push.isEnabled();
        btn.textContent = on ? 'Matikan Notifikasi' : 'Aktifkan Notifikasi';
        btn.classList.toggle('btn-err', !!on);
        btn.classList.toggle('btn-navy', !on);
        if (status) {
          status.textContent = on ? 'Aktif' : 'Belum aktif';
          status.classList.toggle('bg-mint-l', !!on);
          status.classList.toggle('tx-mintt', !!on);
          status.classList.toggle('bg-tealc-m', !on);
          status.classList.toggle('tx-tealt', !on);
        }
      }

      if (!push || !push.supported || !push.configured) {
        btn.disabled = true;
        btn.classList.add('disabled');
        if (status) status.textContent = 'Tidak didukung';
        var note = document.querySelector('[data-push-unsupported]');
        if (note) note.classList.remove('d-none');
        return;
      }

      render();

      btn.addEventListener('click', function () {
        var wasOn = push.isEnabled();
        btn.disabled = true;
        var action = wasOn ? push.disable() : push.enable();
        action.then(function (ok) {
          render();
          if (!wasOn && ok === false && window.AP && window.AP.showToast) {
            window.AP.showToast('Izin notifikasi ditolak atau tidak tersedia di browser ini.', 'error');
          }
        }).catch(function () {}).finally(function () { btn.disabled = false; });
      });
    });
  }

  function initExpiryTimers() {
    document.querySelectorAll('[data-expiry-timer]').forEach(function (el) {
      var iso = el.getAttribute('data-expiry-timer');
      if (!iso) return;
      var expires = new Date(iso);
      function update() {
        var now = Date.now();
        var diff = expires.getTime() - now;
        if (diff <= 0) {
          el.textContent = '(00:00)';
          return;
        }
        var totalSec = Math.floor(diff / 1000);
        var min = Math.floor(totalSec / 60);
        var sec = totalSec % 60;
        el.textContent = '(' + String(min).padStart(2, '0') + ':' + String(sec).padStart(2, '0') + ')';
      }
      update();
      setInterval(update, 1000);
    });
  }

  function boot() {
    initTables();
    initWizards();
    initCharts();
    initEditors();
    initSearchSelects();
    initMultiSelects();
    initAjaxTables();
    initSearchBoxes();
    initNicknameChecks();
    initOperatorChecks();
    initCheckoutTargets();
    initRowLinks();
    initPasswordToggles();
    initPaymentMethods();
    initMinLoaderForms();
    initAmountPresets();
    initCurrencyInputs();
    initCopy();
    initExpiryTimers();
    initTooltips();
    initActions();
    initModals();
    initLoader();
    initPollers();
    initBreadcrumbs();
    initAdminSearch();
    initNotifications();
    initPushToggle();
  }

  if (document.readyState !== 'loading') boot();
  else document.addEventListener('DOMContentLoaded', boot);
})();