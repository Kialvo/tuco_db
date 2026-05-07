<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google sign-in is not configured.']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Google OAuth callback failed: '.$e->getMessage());
            return redirect()->route('login')
                ->withErrors(['email' => 'Could not sign in with Google. Please try again.']);
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $googleUser->getEmail())->first();
        }

        if ($user) {
            $user->fill([
                'google_id'         => $googleUser->getId(),
                'avatar_url'        => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = User::create([
                'name'              => $googleUser->getName() ?: $googleUser->getNickname() ?: $googleUser->getEmail(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'avatar_url'        => $googleUser->getAvatar(),
                'role'              => 'guest',
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, true);

        $defaultRoute = $user->isGuest() ? 'websites.index' : 'dashboard';

        return redirect()->intended(route($defaultRoute, absolute: false));
    }
}
