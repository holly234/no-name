<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'google' => 'Google sign-in could not be completed. Please try again.',
            ]);
        }

        $googleId = (string) $googleUser->getId();
        $email = Str::lower(trim((string) $googleUser->getEmail()));

        if ($googleId === '' || $email === '') {
            return redirect()->route('login')->withErrors([
                'google' => 'Google did not provide the account ID and email required to sign in.',
            ]);
        }

        $user = DB::transaction(function () use ($googleUser, $googleId, $email): User {
            $byGoogleId = User::query()->where('google_id', $googleId)->first();
            $byEmail = User::query()->where('email', $email)->first();

            abort_if($byGoogleId && $byEmail && ! $byGoogleId->is($byEmail), 409, 'This Google account conflicts with an existing account.');

            $user = $byGoogleId ?? $byEmail ?? new User([
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
            ]);

            $user->forceFill([
                'name' => trim((string) $googleUser->getName()) ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleId,
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
            ])->save();

            return $user;
        });

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(
            $user->businesses()->exists()
                ? route('dashboard')
                : route('onboarding.workspace')
        );
    }
}
