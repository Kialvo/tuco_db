<section>
    <header>
        <h2 class="text-base font-bold text-gray-800">Profile information</h2>
        <p class="mt-1 text-sm text-gray-500">Update your display name. Your email identifies you across our apps and can only be changed by an administrator.</p>
    </header>

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
            <div class="flex items-center gap-2 flex-wrap">
                <input id="email" type="email" value="{{ $user->email }}" disabled
                       class="fi py-2.5 flex-1 bg-gray-50 text-gray-500 cursor-not-allowed">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $user->isAdmin() ? 'bg-purple-100 text-purple-700' : ($user->isGuest() ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700') }}">
                    {{ ucfirst($user->role) }}
                </span>
            </div>
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
