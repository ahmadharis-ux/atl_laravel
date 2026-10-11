<div class="card-ap r-card p-3 d-flex flex-column gap-2"
     data-operator-check
     data-endpoint="{{ route('products.detect-operator') }}">
    <div class="d-flex align-items-center gap-2">
        <span class="ibox ibox-40 bg-tealc-m"><span class="ms tx-tealt">sim_card</span></span>
        <div>
            <h2 class="fs-6 fw-bold m-0">Nomor Tujuan</h2>
            <p class="tx-mute fs-12 m-0">Masukkan nomor HP, operator terdeteksi otomatis untuk menampilkan paket.</p>
        </div>
    </div>
    <div class="d-flex flex-column flex-sm-row gap-2 mt-1">
        <input class="ui-input flex-fill" type="tel" inputmode="numeric" autocomplete="off"
               placeholder="Contoh: 081234567890" data-operator-number>
        <button type="button" class="btn-navy justify-content-center" data-operator-submit>
            <span class="ms ms-sm align-middle me-1">search</span>Cek Nomor
        </button>
    </div>
    <div class="fs-12" data-operator-result>
        <span class="tx-skyd" style="opacity:.85">Masukkan nomor untuk melihat paket.</span>
    </div>
</div>
