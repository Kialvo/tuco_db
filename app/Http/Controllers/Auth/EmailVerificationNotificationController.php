<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }

    /**
     * Guest resend (from the "link expired" page — no login required).
     * Enumeration-safe: the response is identical whether or not the
     * address has an account; a mail is actually sent only for existing,
     * still-unverified users.
     */
    public function storePublic(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        $user = \App\Models\User::where('email', mb_strtolower(trim($data['email'])))->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return view('auth.verification-failed', ['sent' => true]);
    }
}
