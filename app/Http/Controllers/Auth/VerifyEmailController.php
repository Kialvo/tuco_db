<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

/**
 * PUBLIC email verification (no login required — the signed URL + email
 * hash are the proof of ownership, checked manually here so failures can
 * render a friendly "link expired" page instead of Laravel's raw 403).
 * Route keeps the stock name/path, so links in already-sent emails work.
 */
class VerifyEmailController extends Controller
{
    public function __invoke(Request $request, string $id, string $hash)
    {
        $user = User::find($id);

        $valid = $user
            && $request->hasValidSignature()
            && hash_equals(sha1($user->getEmailForVerification()), $hash);

        if (! $valid) {
            // Expired, tampered, or unknown — friendly page with a resend form.
            return response()->view('auth.verification-failed', ['sent' => false], 410);
        }

        if ($user->hasVerifiedEmail()) {
            return view('auth.verification-result', ['user' => $user, 'already' => true]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            // Admins hear about a new registration only once it's a real,
            // verified human — bots never reach this point. (Google signups
            // notify at creation in SocialAuthController: pre-verified.)
            \App\Services\NotificationHub::userRegistered($user);
        }

        // The email link NEVER grants access: if this browser still holds
        // the temporary post-registration session for this user, kill it —
        // the only door into the platform is the login page.
        if (auth()->id() === $user->id) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return view('auth.verification-result', ['user' => $user, 'already' => false]);
    }
}
