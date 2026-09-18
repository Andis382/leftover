<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
@include('partials.head')
<title>@yield('title') · {{ __('ui.app_name') }}</title>
</head>
<body>
<a class="skip" href="#main">{{ __('ui.a11y.skip') }}</a>

<header class="topbar">
    <a class="brand" href="{{ route('dashboard') }}" aria-label="{{ __('ui.app_name') }}">
        <span class="crumbs" aria-hidden="true"><i></i><i></i><i></i></span>
        Leftover
    </a>
    <div class="row">
        <span class="who">
            <strong>{{ auth()->user()->displayName() }}</strong>
            {{ auth()->user()->city }}
        </span>
        <a class="iconbtn" href="{{ route('settings.edit') }}" aria-label="{{ __('ui.nav.settings') }}"
           @if (request()->routeIs('settings.*')) aria-current="page" @endif>
            <x-icon name="settings" size="22" />
        </a>
    </div>
</header>

<main class="page @yield('pageclass')" id="main" tabindex="-1">
    @include('partials.flash')
    @yield('content')
</main>

@php($uncounted = auth()->user()->daySheets()
    ->where('status', \App\Models\DaySheet::OPEN)
    ->where('on_date', '<', auth()->user()->today()->toDateString())
    ->count())

<nav class="tabbar" aria-label="{{ __('ui.a11y.main_nav') }}">
    <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
        <x-icon name="home" size="22" />
        <span class="label">{{ __('ui.nav.today') }}</span>
    </a>
    <a href="{{ route('sheet.edit') }}" @if (request()->routeIs('sheet.*')) aria-current="page" @endif>
        <x-icon name="clipboard" size="22" />
        <span class="label">{{ __('ui.nav.count') }}</span>
        @if ($uncounted)
            <span class="dot" aria-hidden="true"></span>
            <span class="sr-only">, {{ trans_choice('ui.a11y.uncounted', $uncounted, ['count' => $uncounted]) }}</span>
        @endif
    </a>
    <a href="{{ route('plan.show') }}" @if (request()->routeIs('plan.*')) aria-current="page" @endif>
        <x-icon name="sunrise" size="22" />
        <span class="label">{{ __('ui.nav.plan') }}</span>
    </a>
    <a href="{{ route('history') }}" @if (request()->routeIs('history')) aria-current="page" @endif>
        <x-icon name="chart" size="22" />
        <span class="label">{{ __('ui.nav.history') }}</span>
    </a>
    <a href="{{ route('products.index') }}" @if (request()->routeIs('products.*')) aria-current="page" @endif>
        <x-icon name="bread" size="22" />
        <span class="label">{{ __('ui.nav.products') }}</span>
    </a>
</nav>

@stack('scripts')
</body>
</html>
