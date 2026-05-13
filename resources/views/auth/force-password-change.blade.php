<x-guest-layout>
    <x-slot name="heading">Set your new password</x-slot>
    <x-slot name="subheading">For security, please change your temporary password</x-slot>

    <form method="POST" action="{{ route('password.force.update') }}" class="space-y-4">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">New password</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="new-password"
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
            Save new password
        </x-ds.button>
    </form>
</x-guest-layout>
