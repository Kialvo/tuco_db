<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = request()->user();

        if (! $user->must_change_password) {
            return redirect()->intended('/');
        }

        return view('auth.force-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->password = Hash::make($validated['password']);
        $user->must_change_password = false;
        $user->save();

        $defaultRoute = $user->isGuest() ? 'websites.index' : 'dashboard';

        return redirect()->route($defaultRoute)
            ->with('status', 'Password updated. Welcome!');
    }
}
