{{-- Input ID/Zone game dengan validasi nickname live. Dipakai di halaman produk & checkout. --}}
<div class="d-flex flex-column gap-2"
     data-nickname-check
     data-endpoint="{{ route('products.nickname') }}"
     data-brand="{{ $brand }}">
    <div>
        <label class="ui-label" for="nickname-target">ID Tujuan</label>
        <input class="ui-input" id="nickname-target" name="target" type="text" inputmode="numeric"
               autocomplete="off" placeholder="Masukkan ID akun" value="{{ old('target', $target) }}" data-nickname-target>
    </div>
    @if ($requiresZone)
        <div>
            <label class="ui-label" for="nickname-zone">Zone ID</label>
            <input class="ui-input" id="nickname-zone" name="zone" type="text" inputmode="numeric"
                   autocomplete="off" placeholder="Masukkan Zone ID" value="{{ old('zone', $zone) }}" data-nickname-zone>
        </div>
    @endif
    <div class="fs-12" data-nickname-result>
        <span class="tx-skyd" style="opacity:.85">Masukkan ID untuk melihat nama akun.</span>
    </div>
</div>
