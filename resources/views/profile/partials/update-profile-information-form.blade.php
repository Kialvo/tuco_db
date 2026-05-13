<section>
    <header>
        <h2 class="text-base font-bold text-gray-800">Profile information</h2>
        <p class="mt-1 text-sm text-gray-500">Update your account's profile information and email address.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                   class="fi py-2.5">
            @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                   class="fi py-2.5">
            @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="text-sm mt-2 text-gray-700">
                    Your email address is unverified.
                    <button form="send-verification" class="font-medium text-green-600 hover:text-green-700">
                        Click here to re-send the verification email.
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 font-medium text-sm text-green-600">
                        A new verification link has been sent to your email address.
                    </p>
                @endif
            @endif
        </div>

        <div class="flex items-center gap-3 pt-2">
            <x-ds.button type="submit" variant="primary">Save</x-ds.button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-green-600">Saved.</p>
            @endif
        </div>
    </form>
</section>
