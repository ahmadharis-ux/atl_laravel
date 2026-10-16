@props([
    'action',
    'name' => 'q',
    'value' => null,
    'placeholder' => 'Cari...',
    'label' => 'Cari',
    'method' => 'GET',
    'history' => [],
    'suggestUrl' => null,
    'clearUrl' => null,
    'suggestLabel' => 'Saran Produk',
    'showHistory' => true,
    'params' => [],
])

<form {{ $attributes->merge(['class' => 'ui-search']) }}
      action="{{ $action }}" method="{{ $method }}" role="search"
      data-suggest-url="{{ $suggestUrl }}" data-clear-url="{{ $clearUrl }}">
    <span class="ms ms-md ui-search-ic" aria-hidden="true">search</span>
    <div class="ui-search-field">
        <input
            type="search"
            class="ui-search-in"
            name="{{ $name }}"
            value="{{ $value ?? old($name) }}"
            placeholder="{{ $placeholder }}"
            aria-label="{{ $placeholder }}"
            autocomplete="off"
        >
        <button type="button" class="ui-search-clear" data-ajax-clear-search data-search-clear
                aria-label="Bersihkan pencarian" @if (! ($value ?? old($name))) hidden @endif>
            <span class="ms" aria-hidden="true">close</span>
        </button>
    </div>
    @if ($label)
        <button type="submit" class="ui-search-go">{{ $label }}</button>
    @endif

    @foreach ($params as $paramName => $paramValue)
        <input type="hidden" name="{{ $paramName }}" value="{{ $paramValue }}">
    @endforeach

    <div class="ui-search-pop" data-role="pop" hidden>
        @if ($showHistory)
            <div class="ui-search-sec" data-role="history">
                <div class="ui-search-head">
                    <span>Sering Dicari</span>
                    <button type="button" class="ui-search-headbtn" data-role="clear-history"
                            @if (! $history) hidden @endif>Hapus</button>
                </div>
                <ul class="ui-search-list" data-role="history-list">
                    @forelse ($history as $q)
                        <li>
                            <button type="button" class="ui-search-row"
                                    data-url="{{ route('products.index', ['q' => $q]) }}"
                                    data-query="{{ $q }}">
                                <span class="ms ui-search-row-ic" aria-hidden="true">history</span>
                                <span class="ui-search-row-tx">{{ $q }}</span>
                                <span class="ms ui-search-row-go" aria-hidden="true">north_west</span>
                            </button>
                        </li>
                    @empty
                        <li class="ui-search-empty" data-role="history-empty">Belum ada pencarian.</li>
                    @endforelse
                </ul>
            </div>
        @endif

        <div class="ui-search-sec" data-role="suggest" hidden>
            <div class="ui-search-head"><span>{{ $suggestLabel }}</span></div>
            <ul class="ui-search-list" data-role="suggest-list"></ul>
        </div>
    </div>
</form>