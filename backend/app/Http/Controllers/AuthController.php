<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Support\Phones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Called once at boot so the XSRF-TOKEN cookie exists before the first POST. */
    public function csrf(): Response
    {
        return response()->noContent();
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'organizationName' => ['required', 'string', 'max:120'],
            'locale' => ['nullable', Rule::in(['en', 'sq'])],
        ]);
        $locale = $data['locale'] ?? config('product.default_locale');

        $user = DB::transaction(function () use ($data, $locale) {
            $org = Organization::create([
                'name' => trim($data['organizationName']),
                'locale' => $locale,
                'timezone' => config('product.default_timezone'),
            ]);

            return User::create([
                'organization_id' => $org->id,
                'name' => trim($data['name']),
                'email' => mb_strtolower(trim($data['email'])),
                'password' => $data['password'],
                'role' => User::OWNER,
                'locale' => $locale,
            ]);
        });

        $this->startSession($request, $user);

        return response()->json($this->me($user), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        if (! Auth::guard('web')->attempt(['email' => mb_strtolower(trim($data['email'])), 'password' => $data['password']], remember: true)) {
            throw ValidationException::withMessages(['email' => [__('auth.failed')]]);
        }
        $request->session()->regenerate();
        /** @var User $user */
        $user = Auth::guard('web')->user();
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json($this->me($user));
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->me($request->user()));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'locale' => ['nullable', Rule::in(['en', 'sq'])],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);
        $user = $request->user();
        $user->name = trim($data['name']);
        if (array_key_exists('phone', $data)) {
            $phone = Phones::normalize($data['phone']);
            if ($phone !== null && ! Phones::isPlausible($phone)) {
                throw ValidationException::withMessages(['phone' => [__('errors.phone_invalid')]]);
            }
            $user->phone = $phone;
        }
        if (! empty($data['locale'])) {
            $user->locale = $data['locale'];
        }
        $user->save();

        return response()->json($this->me($user));
    }

    public function invitation(string $token): JsonResponse
    {
        $invitation = $this->usableInvitation($token);

        return response()->json([
            'organizationName' => $invitation->organization->name,
            'role' => $invitation->role,
            'name' => $invitation->name,
            'expiresAt' => $invitation->expires_at->toIso8601String(),
        ]);
    }

    public function join(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'locale' => ['nullable', Rule::in(['en', 'sq'])],
        ]);
        $invitation = $this->usableInvitation($data['token']);

        $user = DB::transaction(function () use ($data, $invitation) {
            $user = User::create([
                'organization_id' => $invitation->organization_id,
                'name' => trim($data['name']),
                'email' => mb_strtolower(trim($data['email'])),
                'password' => $data['password'],
                'role' => $invitation->role,
                'locale' => $data['locale'] ?? $invitation->organization->locale,
            ]);
            $invitation->forceFill(['accepted_at' => now(), 'accepted_user_id' => $user->id])->save();

            return $user;
        });

        $this->startSession($request, $user);

        return response()->json($this->me($user), 201);
    }

    private function usableInvitation(string $token): Invitation
    {
        $invitation = Invitation::withoutGlobalScope('organization')->with('organization')->where('token', $token)->first();
        abort_if(! $invitation || ! $invitation->isUsable(), 410, __('errors.invitation_invalid'));

        return $invitation;
    }

    private function startSession(Request $request, User $user): void
    {
        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
    }

    private function me(User $user): array
    {
        return [
            'user' => $user->toApi(),
            'organization' => $user->organization->toApi(),
            'demo' => (bool) config('product.demo'),
        ];
    }
}
