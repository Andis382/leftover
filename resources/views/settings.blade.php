@extends('layouts.app')
@section('title', __('ui.settings.title'))

@section('content')
<div class="page-head">
    <span class="micro">{{ $user->displayName() }}</span>
    <h1>{{ __('ui.settings.title') }}</h1>
</div>

<form method="post" action="{{ route('settings.update') }}">
    @csrf @method('PUT')

    <fieldset>
        <legend>{{ __('ui.settings.shop') }}</legend>
        <div class="sheet">
            <x-field name="name" :label="__('ui.settings.name')" :value="$user->name" required />
            <x-field name="shop_name" :label="__('ui.settings.shop_name')" :value="$user->shop_name" optional />
            <div class="cols2">
                <x-field name="city" :label="__('ui.settings.city')" :value="$user->city" />
                <x-field name="phone" type="tel" :label="__('ui.settings.phone')" :value="$user->phone" inputmode="tel" />
            </div>
            <div class="cols2">
                <x-field name="locale" control="select" :label="__('ui.settings.language')"
                         :options="config('leftover.locales')" :selected="$user->locale" />
                <x-field name="currency" control="select" :label="__('ui.settings.currency')"
                         :options="collect(config('leftover.currency_symbols'))->map(fn ($s, $c) => $c.' '.$s)->all()"
                         :selected="$user->currency" />
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>{{ __('ui.settings.hours') }}</legend>
        <div class="sheet">
            <div class="cols2">
                <x-field name="opens_at" type="time" :label="__('ui.settings.opens_at')" :value="$user->opensAt()" required />
                <x-field name="closes_at" type="time" :label="__('ui.settings.closes_at')" :value="$user->closesAt()" required />
            </div>
            <p class="hint">{{ __('ui.settings.hours_hint') }}</p>
        </div>
    </fieldset>

    <fieldset>
        <legend>{{ __('ui.settings.delivery') }}</legend>
        <div class="sheet">
            <x-field name="plan_at" type="time" :label="__('ui.settings.plan_at')"
                     :value="substr((string) $user->plan_at, 0, 5)" :hint="__('ui.settings.plan_at_hint')" required />

            <x-field name="notify_driver" control="select" :label="__('ui.settings.notify_driver')"
                     :selected="$user->notify_driver"
                     :options="[
                        'none' => __('ui.settings.driver_none'),
                        'telegram' => __('ui.settings.driver_telegram'),
                        'log' => __('ui.settings.driver_log'),
                     ]" />

            <x-field name="telegram_chat_id" :label="__('ui.settings.telegram_chat_id')"
                     :value="$user->telegram_chat_id" :hint="__('ui.settings.telegram_hint')" optional />

            @unless ($hasToken)
                <div class="notice nag" role="note">
                    <x-icon name="warning" size="20" />
                    <div>{{ __('ui.settings.telegram_missing') }}</div>
                </div>
            @endunless
        </div>
    </fieldset>

    <button class="btn block big"><x-icon name="check" size="20" />{{ __('ui.settings.save') }}</button>
</form>

<form method="post" action="{{ route('logout') }}" style="margin-top:var(--s-6)">
    @csrf
    <button class="btn ghost block"><x-icon name="logout" size="18" />{{ __('ui.nav.logout') }}</button>
</form>
@endsection
