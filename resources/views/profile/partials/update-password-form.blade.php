<section>
    <header>
        <h2 class="text-base font-bold text-gray-800">Update password</h2>
        <p class="mt-1 text-sm text-gray-500">Use a long, random password to keep your account secure.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-sm font-medium text-gray-700 mb-1.5">Current password</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password"
                   class="fi py-2.5">
            @if($errors->updatePassword->has('current_password'))
                <p class="text-red-600 text-xs mt-1">{{ $errors->updatePassword->first('current_password') }}</p>
            @endif
        </div>

        <div>
            <label for="update_password_password" class="block text-sm font-medium text-gray-700 mb-1.5">New password</label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password"
                   class="fi py-2.5">
            @if($errors->updatePassword->has('password'))
                <p class="text-red-600 text-xs mt-1">{{ $errors->updatePassword->first('password') }}</p>
            @endif
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                   class="fi py-2.5">
            @if($errors->updatePassword->has('password_confirmation'))
                <p class="text-red-600 text-xs mt-1">{{ $errors->updatePassword->first('password_confirmation') }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3 pt-2">
            <x-ds.button type="submit" variant="primary">Save</x-ds.button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-green-600">Saved.</p>
            @endif
        </div>
    </form>
</section>
