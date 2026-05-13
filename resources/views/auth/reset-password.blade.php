<x-guest-layout>
    <x-slot name="heading">Set a new password</x-slot>
    <x-slot name="subheading">Choose a strong password you'll remember</x-slot>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input id="email" name="email" type="email" required autofocus autocomplete="username"
                   value="{{ old('email', $request->email) }}"
                   class="fi py-2.5">
            @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">New password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   placeholder="••••••••" class="fi py-2.5">
            @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   placeholder="••••••••" class="fi py-2.5">
            @error('password_confirmation')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <x-ds.button type="submit" variant="primary" size="lg" block class="mt-2">
            Reset password
        </x-ds.button>
    </form>
</x-guest-layout>
