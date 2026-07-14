<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        // Bot traps: respond with the exact same success flow while creating
        // nothing — a silent drop teaches the bot nothing about what tripped.
        if ($this->looksLikeBot($request)) {
            return redirect()->route('login')
                ->with('status', 'Registration successful! Please check your email to verify your account before logging in.');
        }

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'guest',
        ]);

        event(new Registered($user));

        // NOTE: admins are notified when the email gets VERIFIED
        // (VerifyEmailController), not here — bots never verify, so the
        // notification hub only ever hears about real humans.

        return redirect()->route('login')
            ->with('status', 'Registration successful! Please check your email to verify your account before logging in.');
    }

    /**
     * Honeypot check: bots fill the off-screen decoy field, humans never
     * see it. Every gated attempt is LOGGED so a false positive is visible
     * in laravel.log instead of a silent mystery (lesson from 2026-07-14:
     * the old <3s time-gate + a "website"-named decoy flagged a real user
     * whose browser autofilled the form).
     */
    private function looksLikeBot(Request $request): bool
    {
        if (trim((string) $request->input('contact_ref', '')) === '') {
            return false;
        }

        \Illuminate\Support\Facades\Log::notice('[register bot-gate] honeypot tripped', [
            'ip'           => $request->ip(),
            'email_domain' => str_contains((string) $request->input('email'), '@')
                ? substr(strrchr((string) $request->input('email'), '@'), 1)
                : null,
        ]);

        return true;
    }
}
