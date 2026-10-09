@props(['id', 'type' => 'line', 'config' => ['data' => [], 'options' => []], 'height' => '16rem'])

<div {{ $attributes->merge(['class' => 'chart-wrap']) }} style="height:{{ $height }}">
    <canvas id="{{ $id }}"></canvas>
</div>
<script type="application/json" class="js-chart" data-target="{{ $id }}" data-type="{{ $type }}">{!! json_encode($config) !!}</script>