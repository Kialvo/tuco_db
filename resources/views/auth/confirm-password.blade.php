<x-guest-layout>
    <x-slot name="heading">Confirm password</x-slot>
    <x-slot name="subheading">This is a secure area; please confirm before continuing</x-slot>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   placeholder="••••••••" class="fi py-2.5">
            @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <x-ds.button type="submit" variant="primary" size="lg" block class="mt-2">
            Confirm
        </x-ds.button>
    </form>
</x-guest-layout>
