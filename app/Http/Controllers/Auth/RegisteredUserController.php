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
     * Honeypot + minimum-fill-time check (bots fill the off-screen decoy
     * field and/or submit faster than any human can type the form).
     */
    private function looksLikeBot(Request $request): bool
    {
        // 1) decoy field filled
        if (trim((string) $request->input('website', '')) !== '') {
            return true;
        }

        // 2) submitted less than 3 seconds after the form was rendered,
        //    or with a missing/tampered timestamp
        try {
            $renderedAt = (int) decrypt((string) $request->input('form_time'));
        } catch (\Throwable) {
            return true;
        }

        return (now()->timestamp - $renderedAt) < 3;
    }
}
