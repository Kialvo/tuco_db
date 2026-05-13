<section x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
    <header>
        <h2 class="text-base font-bold text-red-700">Delete account</h2>
        <p class="mt-1 text-sm text-gray-500">
            Once deleted, all of your data is permanently removed. Please download anything you want to keep first.
        </p>
    </header>

    <div class="mt-4">
        <x-ds.button variant="danger" @click="open = true">
            <x-icon name="trash" size="sm" /> Delete account
        </x-ds.button>
    </div>

    {{-- Confirmation modal --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 bg-black/50 items-center justify-center z-50 p-4 flex"
         x-transition.opacity.duration.150ms>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.outside="open = false">
            <h3 class="text-base font-bold text-gray-800">Are you sure?</h3>
            <p class="mt-1 text-sm text-gray-500 leading-relaxed">
                This will permanently delete your account and all associated data. Enter your password to confirm.
            </p>

            <form method="post" action="{{ route('profile.destroy') }}" class="mt-4 space-y-3">
                @csrf
                @method('delete')

                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" placeholder="Password"
                           class="fi py-2.5">
                    @if($errors->userDeletion->has('password'))
                        <p class="text-red-600 text-xs mt-1">{{ $errors->userDeletion->first('password') }}</p>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <x-ds.button variant="ghost" type="button" @click="open = false">Cancel</x-ds.button>
                    <x-ds.button variant="danger" type="submit">Delete account</x-ds.button>
                </div>
            </form>
        </div>
    </div>
</section>
