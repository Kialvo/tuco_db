<x-guest-layout>
    <x-slot name="heading">Create your account</x-slot>
    <x-slot name="subheading">Sign up to access the marketplace</x-slot>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        {{-- Bot traps (see RegisteredUserController::looksLikeBot):
             1) decoy field rendered off-screen — humans never see or fill it,
                naive bots auto-fill every input in the DOM;
             2) encrypted render timestamp — submits faster than a human can
                type (< 3s) are silently dropped. --}}
        <div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
            <label for="website">Website</label>
            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>
        <input type="hidden" name="form_time" value="{{ encrypt(now()->timestamp) }}">

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full name</label>
            <input id="name" name="name" type="text" required autofocus autocomplete="name"
                   value="{{ old('name') }}" placeholder="John Doe"
                   class="fi py-2.5">
            @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input id="email" name="email" type="email" required autocomplete="username"
                   value="{{ old('email') }}" placeholder="you@company.com"
                   class="fi py-2.5">
            @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   placeholder="••••••••" class="fi py-2.5">
            @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   placeholder="••••••••" class="fi py-2.5">
            @error('password_confirmation')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <x-ds.button type="submit" variant="primary" size="lg" block class="mt-2">
            Create account
        </x-ds.button>
    </form>

    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
        <div class="relative flex justify-center text-xs">
            <span class="bg-white px-2 text-gray-400 uppercase tracking-wider">or</span>
        </div>
    </div>

    @include('auth.partials.google-button', ['label' => 'Sign up with Google'])

    <p class="mt-6 text-center text-sm text-gray-500">
        Already have an account?
        <a href="{{ route('login') }}"
           class="group ms-1 inline-flex items-center font-semibold text-green-600 hover:text-green-700">
            Log in
            <x-icon name="arrow-right" size="sm" class="ms-1 transition-transform group-hover:translate-x-0.5" />
        </a>
    </p>
</x-guest-layout>
