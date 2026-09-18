@props(['name', 'size' => 20, 'label' => null])

{{--
    One icon. Decorative by default: nearly every icon here sits next to its own
    visible label, and announcing "flame, Gas boiler" twice is worse than silence.
    Pass :label when the icon is the only thing carrying the meaning, and it
    becomes an image with a name instead.
--}}
@php($body = \App\Support\Icons::body($name))

@if ($body !== '')
<svg class="ico" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 256 256"
     fill="currentColor"
     @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif
     {{ $attributes }}>{!! $body !!}</svg>
@endif
