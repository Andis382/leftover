@extends('layouts.app')
@section('title', __('ui.plan.title'))

@section('content')
<div class="page-head">
    <span class="micro">{{ __('ui.plan.for', ['weekday' => $weekdayName, 'date' => $date->format('d/m/Y')]) }}</span>
    <h1>{{ __('ui.plan.title') }}</h1>
</div>

<div class="filters">
    @for ($ahead = 0; $ahead <= 3; $ahead++)
        @php($day = $user->today()->copy()->addDays($ahead))
        <a class="filter" href="{{ route('plan.show', $day->toDateString()) }}"
           @if ($day->isSameDay($date)) aria-current="true" @endif>
            {{ $ahead === 0 ? __('ui.common.today') : $day->locale($user->locale)->translatedFormat('D j/n') }}
        </a>
    @endfor
</div>

@if (! $plan || $plan->lines->isEmpty())
    <div class="sheet">
        <div class="empty">
            <x-icon name="sunrise" size="40" />
            <p>{{ __('ui.plan.no_plan') }}</p>
        </div>
        <a class="btn block" href="{{ route('sheet.edit') }}">
            <x-icon name="clipboard" size="18" />{{ __('ui.today.count_now') }}
        </a>
    </div>
@else
    @php($changes = $plan->changes())
    @php($same = $plan->unchanged())
    @php($silent = $plan->silent())

    @if ($changes->isNotEmpty())
        <h2>{{ __('ui.plan.changes') }}</h2>
        <div class="sheet flush mark-brand">
            <div class="plan-lines">
                @foreach ($changes as $line)
                    @include('partials.plan-line', ['line' => $line, 'user' => $user, 'weekday' => $weekdayName])
                @endforeach
            </div>
        </div>
    @else
        <div class="sheet mark-level">
            <div class="row">
                <x-icon name="check-circle" size="20" />
                <span class="grow"><strong>{{ __('ui.plan.nothing', ['weekday' => $weekdayName]) }}</strong></span>
            </div>
        </div>
    @endif

    @if ($same->isNotEmpty())
        <h2>{{ __('ui.plan.same') }}</h2>
        <div class="sheet flush">
            <div class="plan-lines">
                @foreach ($same as $line)
                    @include('partials.plan-line', ['line' => $line, 'user' => $user, 'weekday' => $weekdayName])
                @endforeach
            </div>
        </div>
    @endif

    @if ($silent->isNotEmpty())
        <h2>{{ __('ui.plan.unknown') }}</h2>
        <div class="sheet flush">
            <div class="plan-lines">
                @foreach ($silent as $line)
                    @include('partials.plan-line', ['line' => $line, 'user' => $user, 'weekday' => $weekdayName])
                @endforeach
            </div>
        </div>
    @endif

    {{--
        The wording is not a summary of the page; the page is a rendering of the
        wording. Showing it verbatim here is the only way the baker can tell
        whether what lands on his phone at four in the morning is worth reading.
    --}}
    <h2>{{ __('ui.plan.the_message') }}</h2>
    <div class="sheet">
        <div class="draft" id="plan-body">{{ $plan->body }}</div>
        <div class="actions">
            <button type="button" class="btn ghost small" data-copy-target="plan-body">
                <x-icon name="copy" size="15" /><span>{{ __('ui.plan.copy') }}</span>
            </button>
            <a class="btn ghost small" href="https://wa.me/?text={{ rawurlencode($plan->body) }}" target="_blank" rel="noopener">
                <x-icon name="whatsapp" size="15" />{{ __('ui.plan.send_self') }}
            </a>
            <a class="btn ghost small noprint" href="#" onclick="window.print();return false;">
                <x-icon name="printer" size="15" />{{ __('ui.plan.print') }}
            </a>
            <form method="post" action="{{ route('plan.rebuild', $date->toDateString()) }}">
                @csrf
                <button class="btn small"><x-icon name="retry" size="15" />{{ __('ui.plan.rebuild') }}</button>
            </form>
        </div>
    </div>

    <p class="small faint center">{{ __('ui.plan.not_an_order') }}</p>
@endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
  btn.addEventListener('click', async function () {
    var text = document.getElementById(btn.dataset.copyTarget).textContent;
    try { await navigator.clipboard.writeText(text); }
    catch (e) {
      var ta = document.createElement('textarea');
      ta.value = text; document.body.appendChild(ta); ta.select();
      document.execCommand('copy'); ta.remove();
    }
    var label = btn.querySelector('span');
    var original = label.textContent;
    label.textContent = @json(__('ui.plan.copied'));
    setTimeout(function () { label.textContent = original; }, 1500);
  });
});
</script>
@endpush
