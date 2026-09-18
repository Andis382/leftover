@extends('layouts.plain')
@section('title', __('auth.login_title'))
@section('pageclass', 'narrow')

@section('topnav')
    <a class="btn ghost small" href="{{ route('register') }}">{{ __('ui.nav.register') }}</a>
@endsection

@section('content')
<div class="page-head">
    <span class="micro">{{ __('ui.app_name') }}</span>
    <h1>{{ __('auth.login_title') }}</h1>
</div>

<form method="post" action="{{ route('login') }}" class="sheet">
    @csrf
    <x-field name="email" type="email" :label="__('auth.email')" required autofocus autocomplete="email" inputmode="email" />
    <x-field name="password" type="password" :label="__('auth.password_label')" required autocomplete="current-password" />
    <button class="btn block big">{{ __('auth.login_title') }}</button>
</form>

<p class="center small">{{ __('auth.no_account') }} <a href="{{ route('register') }}">{{ __('ui.nav.register') }}</a></p>
@endsection
