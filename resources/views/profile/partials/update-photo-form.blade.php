<section>
    <header>
        <h2 class="text-base font-bold text-gray-800">Profile photo</h2>
        <p class="mt-1 text-sm text-gray-500">Shown next to your comments, replies and notifications. JPG, PNG or WEBP, max 2 MB.</p>
    </header>

    <div class="mt-6 flex items-center gap-5 flex-wrap">
        @if($user->avatar)
            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" id="avatarPreview"
                 class="h-20 w-20 rounded-full object-cover border border-gray-200 shadow-sm">
        @else
            <span id="avatarPreviewWrap" class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-green-100 text-xl font-bold text-green-700 border border-gray-200">
                {{ strtoupper(collect(explode(' ', $user->name))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('')) }}
            </span>
        @endif

        <div class="space-y-3">
            <form method="post" action="{{ route('profile.photo') }}" enctype="multipart/form-data" class="flex items-center gap-3 flex-wrap">
                @csrf
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required
                       class="block text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-green-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-green-700 hover:file:bg-green-100">
                <x-ds.button type="submit" variant="primary">Upload</x-ds.button>
            </form>
            @error('photo')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror

            @if($user->avatar)
                <form method="post" action="{{ route('profile.photo.destroy') }}">
                    @csrf
                    @method('delete')
                    <button type="submit" class="text-sm text-red-500 hover:text-red-700 underline">Remove photo</button>
                </form>
            @endif

            @if (session('status') === 'photo-updated')
                <p class="text-sm text-green-600">Photo updated.</p>
            @elseif (session('status') === 'photo-removed')
                <p class="text-sm text-green-600">Photo removed.</p>
            @endif
        </div>
    </div>
</section>
