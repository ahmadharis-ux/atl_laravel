@if ($searching)
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="d-inline-flex align-items-center gap-1 fs-12 tx-mute fw-semibold">
            <span class="ms ms-sm tx-teal">search</span>
            Semua jenis untuk “{{ $term }}”
        </span>
        <button type="button" class="btn-soft" data-ajax-clear-search>
            <span class="ms ms-sm align-middle me-1">backspace</span>Bersihkan
        </button>
    </div>
@else
    <nav class="ptabs" role="tablist">
        @foreach (\App\Enums\ProductPaymentType::cases() as $t)
            <label>
                <input type="radio" name="payment_type" value="{{ $t->value }}" class="d-none" @checked($paymentType === $t)>
                {{ $t->label() }}
            </label>
        @endforeach
    </nav>
@endif