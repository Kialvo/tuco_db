<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * My Profile — name, photo, password (CRM parity). Available to every
 * verified user, guests included (whitelisted in
 * RestrictGuestToDomainsMiddleware). Email is READ-ONLY here: it's the
 * cross-app identity key (notification hub recipient_email + crm_users
 * matching) — email changes stay an admin action in Manage Users.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    /*======================================================================
    | Name (email deliberately not accepted)
    ======================================================================*/
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update($data);

        return back()->with('status', 'profile-updated');
    }

    /*======================================================================
    | Photo — public disk avatars/. An upload overrides any Google avatar
    | and sticks (SocialAuthController only fills an EMPTY avatar_url).
    ======================================================================*/
    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        $this->deleteLocalAvatar($user->avatar_url);

        $path = $request->file('photo')->store('avatars', 'public');
        $user->update(['avatar_url' => $path]);

        return back()->with('status', 'photo-updated');
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->deleteLocalAvatar($user->avatar_url);
        $user->update(['avatar_url' => null]);

        return back()->with('status', 'photo-removed');
    }

    /** Delete a previous LOCAL upload; external URLs (Google) are left alone. */
    private function deleteLocalAvatar(?string $value): void
    {
        if ($value && ! str_starts_with($value, 'http')) {
            Storage::disk('public')->delete($value);
        }
    }
}
