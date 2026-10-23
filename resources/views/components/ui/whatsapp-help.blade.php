@props(['url' => null, 'label' => 'Hubungi CS via WhatsApp'])

@php($whatsappUrl = $url ?? \App\Support\SupportWhatsapp::generalUrl())

@if ($whatsappUrl)
    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
        {{ $attributes->merge(['class' => 'btn-ap btn-ap-outline justify-content-center']) }}>
        <span class="ms ms-sm align-middle me-1" aria-hidden="true">chat</span>{{ $label }}
    </a>
@endif
