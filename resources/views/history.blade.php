@extends('layouts.app')
@section('title', __('ui.history.title'))

@section('content')
<div class="page-head">
    <span class="micro">{{ $from->format('d/m') }} – {{ $to->format('d/m/Y') }}</span>
    <h1>{{ __('ui.history.title') }}</h1>
</div>

<div class="filters">
    @foreach ([7, 28, 90] as $option)
        <a class="filter" href="{{ route('history', ['days' => $option]) }}"
           @if ($days === $option) aria-current="true" @endif>{{ __('ui.history.range', ['days' => $option]) }}</a>
    @endforeach
</div>

@if ($totals['days'] === 0)
    <div class="sheet">
        <div class="empty">
            <x-icon name="chart" size="40" />
            <p>{{ __('ui.history.none') }}</p>
        </div>
    </div>
@else

{{--
    The number that changes a mind. Nobody acts on "8% waste"; people act on a
    currency symbol followed by what that is in a month. It is put in the
    display face, at the top, before anything else on the page.
--}}
<div class="sheet mark-over">
    <span class="micro">{{ __('ui.history.in_money') }}</span>
    <div class="bigmoney">{{ $user->money($totals['value']) }}</div>
    <p class="small soft">
        {{ __('ui.history.total_waste') }}: <span class="n">{{ $totals['left'] }}</span>
        · {{ __('ui.history.of_production', ['rate' => $totals['rate']]) }}
        · {{ __('ui.history.sold_out_lines', ['n' => $totals['sold_out']]) }}
    </p>
</div>

<h2>{{ __('ui.history.worst') }}</h2>
<div class="sheet flush">
    <div class="rows">
        @foreach ($worst as $row)
            <div class="entry">
                <span class="glyph"><x-icon :name="$row['product']?->icon() ?? 'basket'" size="19" /></span>
                <span class="entry-body">
                    <span class="entry-title">{{ $row['product']?->name }}</span>
                    <span class="entry-sub">
                        <span class="n">{{ $row['left'] }}</span> {{ __('ui.history.total_waste') }}
                        · {{ __('ui.history.of_production', ['rate' => $row['rate']]) }}
                        @if ($row['sold_out'] > 0) · {{ __('ui.history.sold_out_lines', ['n' => $row['sold_out']]) }} @endif
                    </span>
                    <span class="bar" aria-hidden="true"><span style="width: {{ min(100, $row['rate'] * 4) }}%"></span></span>
                </span>
                <span class="entry-side">
                    <strong class="n">{{ $user->money($row['value']) }}</strong>
                </span>
            </div>
        @endforeach
    </div>
</div>

<h2>{{ __('ui.history.by_day') }}</h2>
<div class="sheet flush">
    <div class="rows">
        @foreach ($daily as $row)
            @php($rate = $row['baked'] > 0 ? round($row['left'] / $row['baked'] * 100, 1) : 0)
            <a class="entry" href="{{ route('sheet.edit', $row['sheet']->on_date->toDateString()) }}">
                <span class="entry-body">
                    <span class="entry-title">{{ $row['sheet']->on_date->locale($user->locale)->translatedFormat('l j F') }}</span>
                    <span class="entry-sub">
                        {{ __('ui.today.baked') }} <span class="n">{{ $row['baked'] }}</span>
                        · {{ __('ui.today.waste') }} <span class="n">{{ $row['left'] }}</span>
                        @if ($row['sold_out'] > 0) · {{ __('ui.history.sold_out_lines', ['n' => $row['sold_out']]) }} @endif
                    </span>
                    <span class="bar {{ $row['left'] === 0 ? 'is-level' : '' }}" aria-hidden="true">
                        <span style="width: {{ min(100, $rate * 4) }}%"></span>
                    </span>
                </span>
                <span class="entry-side">
                    <strong class="n">{{ $user->money($row['value']) }}</strong>
                    @if ($row['left'] === 0)
                        <x-tag tone="level" icon="check">{{ __('ui.history.nothing_wasted') }}</x-tag>
                    @endif
                </span>
            </a>
        @endforeach
    </div>
</div>
@endif
@endsection
