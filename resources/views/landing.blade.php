@extends('layouts.plain')
@section('title', __('ui.tagline'))
@section('description', 'A forty-second closing count for a small bakery, and a bake plan at four in the morning that gets a little less wrong every week.')

@section('topnav')
    <a class="btn ghost small" href="{{ route('login') }}">{{ __('ui.nav.login') }}</a>
    <a class="btn small" href="{{ route('register') }}">{{ __('ui.nav.register') }}</a>
@endsection

@section('content')
<div class="hero">
    <span class="micro">{{ __('ui.app_name') }}</span>
    <h1>{{ __('ui.tagline') }}</h1>
    <p class="lead">
        A small bakery decides at four in the morning how many of each of thirty things to bake, by feel.
        Whatever is left at closing goes in a bag out the back. Whatever sold out by ten is a queue of
        people nobody counted. Neither number is written down anywhere, so the same Monday over-bake and
        the same Saturday shortage repeat for years.
    </p>
</div>

<div class="three">
    <div>
        <span class="n">01</span>
        <h3>Forty seconds at closing</h3>
        <p>A ruled sheet on your phone: baked, left, and — if the shelf emptied — roughly when. Skip anything you did not bake. Nothing here is compulsory.</p>
    </div>
    <div>
        <span class="n">02</span>
        <h3>A plan before you get up</h3>
        <p>At the hour you choose, tomorrow's numbers, worked out from the last few of the same weekday. In words: five fewer of these, ten more of those.</p>
    </div>
    <div>
        <span class="n">03</span>
        <h3>A little less wrong each week</h3>
        <p>Every count sharpens it. After a month you can see, in money, what the bin has been taking — which is the number that changes what you bake.</p>
    </div>
</div>

{{-- The product is this message. A screenshot of a form persuades nobody. --}}
<div class="example">
    <span class="micro">Tuesday, 04:00</span>
    <div class="draft" style="margin-top:var(--s-2)">Furra Petrela
Bake plan for Tuesday 22/09

Worth changing:
• 6 fewer croissants, so 42. About 7 left over each time.
• 10 more sesame rolls, so 70. Sold out on 3 of them.

Same as usual: white loaf 120, burek 40

Not enough counts yet: apple pie

These are suggestions from your own counts. You know things they do not.</div>
</div>

<h2>Why this and not a forecasting platform</h2>
<p class="lead">
    Bakery forecasting exists and it works — Delicious Data and foodforecast sell it to chains, priced per
    branch and fed by a POS export. A one-shop bakery has no POS export, no back office and nobody entering
    anything into a computer at the end of the day. The gap is not the mathematics. The gap is that nothing
    asks for the one number the till can never know: what was still on the shelf at closing.
</p>

<div class="sheet mark-short">
    <span class="micro">The idea the till cannot have</span>
    <p class="small">
        A sell-out looks like a perfect day in every report you will ever run, because a sale that never
        happened leaves no record anywhere. Here it is a fact you record on purpose, with roughly what time
        it happened, and a day that emptied at ten in the morning counts as more demand than it served.
        That single correction is the difference between averaging your till and learning what people
        actually wanted.
    </p>
</div>

<h2>What it will not do</h2>
<ul class="lead">
    <li>It will not tell you to halve your Saturday off three weeks of data. No suggestion moves production by more than a fifth.</li>
    <li>It says nothing at all about a product until it has two counted days of that weekday, and it tells you how many it has.</li>
    <li>It never scolds. A missed count makes the sample smaller, not a zero, and nothing is ever sent to you about a day you skipped.</li>
</ul>

<a class="btn big block" href="{{ route('register') }}">{{ __('ui.nav.register') }}</a>
<p class="center small faint">Open source. Run it yourself, or read exactly what it does with your numbers.</p>
@endsection
