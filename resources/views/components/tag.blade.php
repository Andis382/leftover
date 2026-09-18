@props(['tone' => 'quiet', 'icon' => null])

{{--
    A status tag. Colour is the third signal, after the icon and the word, so
    the same tag still means the same thing photocopied, printed in black and
    white, or read by someone who cannot tell the red one from the green one.
--}}
<span {{ $attributes->merge(['class' => 'tag '.$tone]) }}>
    @if ($icon)<x-icon :name="$icon" size="13" />@endif
    <span class="label">{{ $slot }}</span>
</span>
