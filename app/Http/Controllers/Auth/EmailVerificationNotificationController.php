<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class EmailVerificationNotificationController extends Controller
{
    /**
     * One verification email per user per this many seconds — enforced
     * SERVER-side (the button countdown is just a mirror of this).
     */
    public const COOLDOWN_SECONDS = 60;

    public static function limiterKey(int $userId): string
    {
        return 'verify-send:' . $userId;
    }

    /**
     * Send a new email verification notification (authed Resend button).
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $key = self::limiterKey($request->user()->id);

        if (RateLimiter::tooManyAttempts($key, 1)) {
            // Too early — the Verify page re-renders with the live countdown.
            return back()->with('status', 'verification-throttled');
        }

        RateLimiter::hit($key, self::COOLDOWN_SECONDS);
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }

    /**
     * Guest resend (from the "link expired" page — no login required).
     * Enumeration-safe: the response is identical whether or not the
     * address has an account; a mail is actually sent only for existing,
     * still-unverified users OUTSIDE their 60s cooldown.
     */
    public function storePublic(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        $user = \App\Models\User::where('email', mb_strtolower(trim($data['email'])))->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $key = self::limiterKey($user->id);
            if (! RateLimiter::tooManyAttempts($key, 1)) {
                RateLimiter::hit($key, self::COOLDOWN_SECONDS);
                $user->sendEmailVerificationNotification();
            }
        }

        return view('auth.verification-failed', ['sent' => true]);
    }
}
