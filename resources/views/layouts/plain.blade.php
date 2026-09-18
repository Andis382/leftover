<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
@include('partials.head')
<title>@yield('title') · {{ __('ui.app_name') }}</title>
@hasSection('description')
<meta name="description" content="@yield('description')">
@endif
</head>
<body>
<a class="skip" href="#main">{{ __('ui.a11y.skip') }}</a>

<header class="topbar">
    <a class="brand" href="{{ route('home') }}" aria-label="{{ __('ui.app_name') }}">
        <span class="crumbs" aria-hidden="true"><i></i><i></i><i></i></span>
        Leftover
    </a>
    <nav class="row" aria-label="{{ __('ui.a11y.main_nav') }}">
        @yield('topnav')
    </nav>
</header>

<main class="page plain @yield('pageclass')" id="main" tabindex="-1">
    @include('partials.flash')
    @yield('content')
</main>

@stack('scripts')
</body>
</html>
