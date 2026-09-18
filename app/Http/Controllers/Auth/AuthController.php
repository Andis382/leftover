<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($data, $request->boolean('remember', true))) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'shop_name' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'locale' => ['nullable', Rule::in(array_keys(config('leftover.locales')))],
        ]);

        $user = User::create($data + [
            'locale' => $data['locale'] ?? config('leftover.defaults.locale'),
            'currency' => config('leftover.defaults.currency'),
            'timezone' => config('leftover.defaults.timezone'),
            'opens_at' => config('leftover.defaults.opens_at'),
            'closes_at' => config('leftover.defaults.closes_at'),
            'plan_at' => config('leftover.defaults.plan_at'),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        // Straight to the product list: a bakery with no products has nothing
        // to count, and this is the only setup step that actually matters.
        return redirect()->route('products.index')->with('status', __('flash.welcome'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
