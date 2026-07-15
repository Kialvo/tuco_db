<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();
        $defaultRoute = $user->isGuest() ? 'websites.index' : 'dashboard';

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route($defaultRoute, absolute: false));
        }

        // Server-side truth for the Resend cooldown: seconds until the next
        // send is allowed (0 = button active). The blade counts down from
        // this — new tabs/incognito can't reset it.
        $key = EmailVerificationNotificationController::limiterKey($user->id);
        $secondsLeft = \Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 1)
            ? \Illuminate\Support\Facades\RateLimiter::availableIn($key)
            : 0;

        return view('auth.verify-email', ['secondsLeft' => $secondsLeft]);
    }
}
