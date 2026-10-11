@if ($searching)
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="d-inline-flex align-items-center gap-1 fs-12 tx-mute fw-semibold">
            <span class="ms ms-sm tx-teal">search</span>
            Semua jenis untuk “{{ $term }}”
        </span>
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