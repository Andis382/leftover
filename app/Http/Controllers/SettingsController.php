<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('settings', [
            'user' => $request->user(),
            'driver' => config('leftover.notify.driver'),
            'hasToken' => (bool) config('leftover.notify.telegram_token'),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'shop_name' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'locale' => ['required', Rule::in(array_keys(config('leftover.locales')))],
            'currency' => ['required', Rule::in(array_keys(config('leftover.currency_symbols')))],
            'opens_at' => ['required', 'date_format:H:i'],
            'closes_at' => ['required', 'date_format:H:i', 'after:opens_at'],
            'plan_at' => ['required', 'date_format:H:i'],
            'notify_driver' => ['required', Rule::in(['none', 'telegram', 'log'])],
            'telegram_chat_id' => ['nullable', 'string', 'max:40'],
        ]);

        $user->update($data);

        return redirect()->route('settings.edit')->with('status', __('flash.saved'));
    }
}
