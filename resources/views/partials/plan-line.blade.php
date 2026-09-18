{{--
    One line of the plan. The move first, as a glyph and a signed number, then
    the sentence, then the reason, then how many counted days it stands on.
    That last line is not a footnote: a number built on two Tuesdays and a
    number built on eight are different kinds of number, and the screen says
    which one it is holding rather than sounding equally sure about both.
--}}
@php($tone = $line->tone())
<div class="plan-line">
    <span class="plan-move {{ $tone }}" aria-hidden="true">
        <span class="d">{{ $line->suggested_qty === null ? '?' : ($line->delta > 0 ? '+'.$line->delta : ($line->delta < 0 ? $line->delta : '=')) }}</span>
        <span class="u">{{ $line->suggested_qty ?? '—' }}</span>
    </span>
    <span class="plan-body">
        <span class="what">{{ $line->sentence() }}</span>
        <span class="why">{{ $line->reasonSentence() }}</span>
        @if ($line->observations > 0)
            <span class="basis">
                {{ trans_choice('ui.plan.based_on', $line->observations, [
                    'count' => $line->observations,
                    'weekday' => $weekday,
                ]) }}
                @if ($line->previous_qty !== null)
                    · {{ __('ui.plan.previous', ['n' => $line->previous_qty]) }}
                @endif
            </span>
        @endif
    </span>
</div>
