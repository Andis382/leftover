@extends('layouts.app')
@section('title', __('ui.sheet.title'))

@section('content')
<div class="page-head">
    <span class="micro">{{ $date->locale($user->locale)->translatedFormat('l j F') }}</span>
    <h1>{{ __('ui.sheet.title') }}</h1>
    <p>{{ __('ui.sheet.subtitle') }}</p>
</div>

@if ($sheet->status === \App\Models\DaySheet::SHUT)
    <div class="notice nag" role="status">
        <x-icon name="prohibit" size="20" />
        <div>
            {{ __('sheet.status.shut') }}
            <form method="post" action="{{ route('sheet.mark', $date->toDateString()) }}" style="display:inline">
                @csrf <input type="hidden" name="status" value="open">
                <button class="linkbtn">{{ __('ui.sheet.reopen') }}</button>
            </form>
        </div>
    </div>
@endif

@if ($products->isEmpty())
    <div class="sheet">
        <div class="empty">
            <x-icon name="bread" size="40" />
            <p>{{ __('ui.today.no_products_body') }}</p>
        </div>
        <a class="btn block" href="{{ route('products.index') }}">{{ __('ui.today.no_products') }}</a>
    </div>
@else

{{--
    The tally. One ruled row per product: the name under the margin, the two
    numbers out to the right where a form puts them, and the sell-out on its own
    line because it is a different kind of fact from a quantity.

    Nothing here is required. A sheet with four rows filled in is a real record
    and the blank rows are simply absent from the history — not zero, which
    would teach the forecast that nobody bought any bread on Thursday.
--}}
<form method="post" action="{{ route('sheet.update', $date->toDateString()) }}" id="tally-form">
    @csrf

    <div class="tally">
        @foreach ($products as $product)
            @php($count = $existing->get($product->id))
            @php($last = $lastSameCounts->get($product->id))
            <div class="tally-row {{ $count ? 'filled' : '' }}" data-row="{{ $product->id }}">
                <div class="tally-name">
                    <span class="grow">{{ $product->name }}</span>
                    <span class="unit">{{ __('product.unit_short.'.$product->unit) }}</span>
                </div>

                <span class="tally-last">
                    @if ($last)
                        {{ __('ui.sheet.last_time', ['weekday' => $date->locale($user->locale)->translatedFormat('l')]) }}:
                        @if ($last->sold_out_at)
                            {{ __('ui.sheet.last_time_sold_out', [
                                'baked' => $last->baked_qty,
                                'time' => substr((string) $last->sold_out_at, 0, 5),
                            ]) }}
                        @else
                            {{ __('ui.sheet.last_time_line', ['baked' => $last->baked_qty, 'left' => $last->left_qty]) }}
                        @endif
                    @else
                        {{ __('ui.sheet.never_counted') }}
                    @endif
                </span>

                <div class="tally-fields">
                    <div class="tally-field">
                        <label class="micro" for="baked-{{ $product->id }}">{{ __('ui.sheet.baked') }}</label>
                        <input class="qty" type="number" inputmode="numeric" min="0" max="9999"
                               id="baked-{{ $product->id }}"
                               name="entries[{{ $product->id }}][baked]"
                               value="{{ old('entries.'.$product->id.'.baked', $count?->baked_qty) }}"
                               placeholder="{{ $product->typical_batch ?: '—' }}">
                    </div>
                    <div class="tally-field">
                        <label class="micro" for="left-{{ $product->id }}">{{ __('ui.sheet.left') }}</label>
                        <input class="qty leftbox" type="number" inputmode="numeric" min="0" max="9999"
                               id="left-{{ $product->id }}"
                               name="entries[{{ $product->id }}][left]"
                               value="{{ old('entries.'.$product->id.'.left', $count?->left_qty) }}"
                               placeholder="0">
                    </div>
                </div>

                @php($soldOut = old('entries.'.$product->id.'.sold_out_at', $count?->sold_out_at ? substr((string) $count->sold_out_at, 0, 5) : null))
                <label class="soldout" for="so-{{ $product->id }}">
                    <input type="checkbox" id="so-{{ $product->id }}" class="so-toggle"
                           data-product="{{ $product->id }}" @checked($soldOut)>
                    <span class="label">{{ __('ui.sheet.sold_out') }}</span>
                    <span class="when {{ $soldOut ? '' : 'hidden' }}" data-when="{{ $product->id }}">
                        <label class="sr-only" for="sot-{{ $product->id }}">{{ __('ui.sheet.sold_out_at') }}</label>
                        <input type="time" id="sot-{{ $product->id }}"
                               name="entries[{{ $product->id }}][sold_out_at]"
                               value="{{ $soldOut ?: $user->closesAt() }}"
                               @disabled(! $soldOut)>
                    </span>
                </label>
            </div>
        @endforeach
    </div>

    <p class="hint">{{ __('ui.sheet.sold_out_hint') }}</p>

    <x-field name="note" control="textarea" :label="__('ui.sheet.note')" :hint="__('ui.sheet.note_hint')"
             :value="$sheet->note" maxlength="500" />

    <div class="stickysave">
        <button type="submit" class="btn big block">
            <x-icon name="check" size="20" />
            {{ __('ui.sheet.save') }}
        </button>
    </div>
</form>

@if ($sheet->counted_at)
    <p class="small faint center">{{ __('ui.sheet.saved_at', ['time' => $sheet->counted_at->timezone($user->timezone)->format('H:i')]) }}</p>
@endif

<form method="post" action="{{ route('sheet.mark', $date->toDateString()) }}"
      onsubmit="return confirm(@js(__('ui.sheet.shut_confirm')))">
    @csrf <input type="hidden" name="status" value="shut">
    <button class="btn ghost block"><x-icon name="prohibit" size="18" />{{ __('ui.sheet.shut_today') }}</button>
</form>
@endif

<details class="more">
    <summary>{{ __('ui.sheet.other_day') }}</summary>
    <div class="inner">
        <div class="filters">
            @for ($back = 1; $back <= 7; $back++)
                @php($day = $user->today()->copy()->subDays($back))
                <a class="filter" href="{{ route('sheet.edit', $day->toDateString()) }}"
                   @if ($day->isSameDay($date)) aria-current="true" @endif>
                    {{ $day->locale($user->locale)->translatedFormat('D j/n') }}
                </a>
            @endfor
        </div>
    </div>
</details>
@endsection

@push('scripts')
<script>
(function () {
  // A number in "left" is worth looking at: none is the good outcome, some is
  // the expensive one. The colour follows the value so the sheet reads back at
  // a glance once it is filled in.
  function paint(box) {
    box.classList.remove('left-none', 'left-some');
    if (box.value === '') return;
    box.classList.add(Number(box.value) > 0 ? 'left-some' : 'left-none');
  }
  document.querySelectorAll('.leftbox').forEach(function (box) {
    paint(box);
    box.addEventListener('input', function () { paint(box); });
  });

  // The margin rule fills in as you go down the sheet.
  document.querySelectorAll('.tally-row').forEach(function (row) {
    var baked = row.querySelector('input[name$="[baked]"]');
    if (!baked) return;
    baked.addEventListener('input', function () {
      row.classList.toggle('filled', baked.value !== '');
    });
  });

  // Sold out reveals the time and enables it, so an unticked box submits
  // nothing at all rather than a stray closing time.
  document.querySelectorAll('.so-toggle').forEach(function (toggle) {
    toggle.addEventListener('change', function () {
      var id = toggle.dataset.product;
      var wrap = document.querySelector('[data-when="' + id + '"]');
      var time = document.getElementById('sot-' + id);
      var left = document.getElementById('left-' + id);
      wrap.classList.toggle('hidden', !toggle.checked);
      time.disabled = !toggle.checked;
      // Sold out and "six left" cannot both be true. The count wins.
      if (toggle.checked && left && Number(left.value) > 0) { left.value = 0; left.dispatchEvent(new Event('input')); }
    });
  });
})();
</script>
@endpush
