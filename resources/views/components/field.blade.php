@props([
    'name',
    'label',
    'type' => 'text',
    'control' => 'input',
    'value' => null,
    'hint' => null,
    'optional' => false,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
])

{{--
    One field: a visible label, the control, the hint, and the error for this
    field underneath it — never a wall of errors at the top with no idea which
    box is wrong. The id is derived from the field name so the summary above the
    form can link straight to it.
--}}
@php
    $id = 'f-'.str_replace(['[', ']', '.', '_'], ['-', '', '-', '-'], $name);
    $error = $errors->first($name);
    $described = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
    $current = old($name, $value);
@endphp

<div class="field">
    <label class="name" for="{{ $id }}">
        {{ $label }}@if ($optional) <span class="opt">({{ __('ui.common.optional') }})</span>@endif
    </label>

    @if ($control === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}"
                  @if ($described) aria-describedby="{{ $described }}" @endif
                  @if ($error) aria-invalid="true" @endif
                  {{ $attributes }}>{{ $current }}</textarea>
    @elseif ($control === 'select')
        <select id="{{ $id }}" name="{{ $name }}"
                @if ($described) aria-describedby="{{ $described }}" @endif
                @if ($error) aria-invalid="true" @endif
                {{ $attributes }}>
            @foreach ($options as $key => $text)
                <option value="{{ $key }}" @selected((string) old($name, $selected) === (string) $key)>{{ $text }}</option>
            @endforeach
        </select>
    @elseif ($control === 'slot')
        {{ $slot }}
    @else
        <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}"
               @if ($type !== 'file') value="{{ $current }}" @endif
               @if ($placeholder) placeholder="{{ $placeholder }}" @endif
               @if ($described) aria-describedby="{{ $described }}" @endif
               @if ($error) aria-invalid="true" @endif
               {{ $attributes }}>
    @endif

    @if ($hint)
        <p class="hint" id="{{ $id }}-hint">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="field-error" id="{{ $id }}-error">
            <x-icon name="warning-circle" size="15" />
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
