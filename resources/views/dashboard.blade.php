@extends('layouts.app')
@section('title', __('ui.today.title'))

@section('content')
<div class="page-head">
    <span class="micro">{{ $today->locale($user->locale)->translatedFormat('l j F Y') }}</span>
    <h1>{{ $user->displayName() }}</h1>
</div>

@if ($products === 0)
    <div class="sheet mark-brand">
        <div class="empty">
            <x-icon name="bread" size="44" />
            <p>{{ __('ui.today.no_products_body') }}</p>
        </div>
        <a class="btn big block" href="{{ route('products.index') }}">
            <x-icon name="plus" size="20" />{{ __('ui.today.no_products') }}
        </a>
    </div>
@else

{{--
    The one nag this product allows itself, and it appears here and nowhere
    else. Nothing is sent about a missed day: a tool that texts a baker at ten
    at night to say he forgot something is a tool that gets muted by Friday.
--}}
@if ($unfinished)
    <div class="sheet mark-short">
        <div class="row between">
            <span class="micro">{{ __('ui.today.unfinished') }}</span>
            <x-tag tone="short" icon="clock">{{ $unfinished->on_date->format('d/m') }}</x-tag>
        </div>
        <p class="small">{{ __('ui.today.unfinished_body') }}</p>
        <div class="actions">
            <a class="btn small" href="{{ route('sheet.edit', $unfinished->on_date->toDateString()) }}">
                <x-icon name="clipboard" size="15" />{{ __('ui.today.count_yesterday') }}
            </a>
            <form method="post" action="{{ route('sheet.mark', $unfinished->on_date->toDateString()) }}">
                @csrf <input type="hidden" name="status" value="shut">
                <button class="btn ghost small">{{ __('ui.today.was_shut') }}</button>
            </form>
        </div>
    </div>
@endif

@if ($sheet && $sheet->isCounted())
    <a class="btn ghost block" href="{{ route('sheet.edit') }}">
        <x-icon name="check-circle" size="18" />
        {{ __('ui.today.counted', ['time' => $sheet->counted_at?->timezone($user->timezone)->format('H:i')]) }} · {{ __('ui.today.fix_count') }}
    </a>
@else
    <a class="btn big block" href="{{ route('sheet.edit') }}">
        <x-icon name="clipboard" size="20" />
        {{ __('ui.today.count_now') }}
    </a>
@endif

{{--
    Two numbers side by side, and they are opposite mistakes of the same size.
    Waste is the one everybody sees, in a bag, at the back door. Sold out is the
    one nobody sees at all, because a sale that never happened leaves no trace
    in any till in the world. Giving it equal weight on this page is the whole
    argument of the product.
--}}
<div class="figures">
    <div class="figure is-over">
        <span class="micro">{{ __('ui.today.waste') }}</span>
        <span class="v n">{{ $week['left'] }}</span>
        <span class="foot">{{ __('ui.history.of_production', ['rate' => $week['rate']]) }}</span>
    </div>
    <div class="figure is-short">
        <span class="micro">{{ __('ui.today.sold_out') }}</span>
        <span class="v n">{{ $week['sold_out'] }}</span>
        <span class="foot">{{ __('ui.today.week') }}</span>
    </div>
    <div class="figure">
        <span class="micro">{{ __('ui.today.baked') }}</span>
        <span class="v n">{{ $week['baked'] }}</span>
        <span class="foot">{{ __('ui.today.week') }}</span>
    </div>
    <a class="figure" href="{{ route('history') }}">
        <span class="micro">{{ __('ui.today.waste_value') }}</span>
        <span class="v n">{{ $user->money($week['value']) }}</span>
        <span class="foot">
            @php($diff = round($week['value'] - $previousWeek['value'], 2))
            @if ($previousWeek['days'] === 0)
                {{ __('ui.today.week') }}
            @elseif (abs($diff) < 0.5)
                {{ __('ui.today.same_as_last_week') }}
            @else
                {{ __($diff < 0 ? 'ui.today.better' : 'ui.today.worse', ['n' => $user->money(abs($diff))]) }}
            @endif
        </span>
    </a>
</div>

<h2>{{ __('ui.today.tomorrow') }}</h2>
@if ($tomorrow && $tomorrow->lines->isNotEmpty())
    <div class="sheet flush mark-brand">
        <div class="plan-lines">
            @foreach ($tomorrow->changes()->take(4) as $line)
                @include('partials.plan-line', [
                    'line' => $line,
                    'user' => $user,
                    'weekday' => $tomorrow->for_date->locale($user->locale)->translatedFormat('l'),
                ])
            @endforeach
            @if ($tomorrow->changes()->isEmpty())
                <div class="plan-line">
                    <span class="plan-move level"><span class="d">=</span><span class="u">ok</span></span>
                    <span class="plan-body">
                        <span class="what">{{ __('ui.plan.nothing', ['weekday' => $tomorrow->for_date->locale($user->locale)->translatedFormat('l')]) }}</span>
                    </span>
                </div>
            @endif
        </div>
        <div class="sheet-foot">
            <a class="btn ghost small" href="{{ route('plan.show', $tomorrow->for_date->toDateString()) }}">
                <x-icon name="sunrise" size="15" />{{ __('ui.today.see_plan') }}
            </a>
        </div>
    </div>
@else
    <div class="sheet">
        <div class="empty">
            <x-icon name="sunrise" size="36" />
            <p>{{ __('ui.plan.no_plan') }}</p>
        </div>
        <a class="btn ghost block" href="{{ route('plan.show') }}">{{ __('ui.today.see_plan') }}</a>
    </div>
@endif
@endif
@endsection
