<x-guest-layout>
    <div class="mb-4 text-sm text-gray-700 dark:text-gray-300">
        For security reasons, please set a new password before continuing.
    </div>

    <form method="POST" action="{{ route('password.force.update') }}">
        @csrf

        <div>
            <x-input-label for="password" :value="__('New password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                {{ __('Save new password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
