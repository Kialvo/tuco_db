<x-guest-layout>
    <x-slot name="heading">Welcome back</x-slot>
    <x-slot name="subheading">Sign in to access the marketplace</x-slot>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input id="email" name="email" type="email" required autofocus autocomplete="username"
                   value="{{ old('email') }}" placeholder="you@company.com"
                   class="fi py-2.5">
            @error('email')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   placeholder="••••••••"
                   class="fi py-2.5">
            @error('password')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center cursor-pointer select-none">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500">
                <span class="ms-2 text-sm text-gray-600">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="text-sm font-medium text-green-600 hover:text-green-700">
                    Forgot password?
                </a>
            @endif
        </div>

        <x-ds.button type="submit" variant="primary" size="lg" block>
            Sign in
        </x-ds.button>
    </form>

    {{-- Divider --}}
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center text-xs">
            <span class="bg-white px-2 text-gray-400 uppercase tracking-wider">or</span>
        </div>
    </div>

    @include('auth.partials.google-button', ['label' => 'Continue with Google'])

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-gray-500">
            Don't have an account?
            <a href="{{ route('register') }}"
               class="group ms-1 inline-flex items-center font-semibold text-green-600 hover:text-green-700">
                Create one
                <x-icon name="arrow-right" size="sm" class="ms-1 transition-transform group-hover:translate-x-0.5" />
            </a>
        </p>
    @endif
</x-guest-layout>
