<x-guest-layout>
    <x-slot name="heading">This link has expired</x-slot>
    <x-slot name="subheading">Verification links are valid for a limited time.</x-slot>

    <div class="text-center space-y-6">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <p class="text-sm text-gray-600">
            The verification link is invalid or has expired. Enter your email below and we'll send you a fresh one.
        </p>

        @if($sent)
            <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                If an unverified account exists for this address, a new verification link is on its way. Check your inbox.
            </div>
        @endif

        <form method="POST" action="{{ route('verification.resend.public') }}" class="space-y-3">
            @csrf
            <input name="email" type="email" required placeholder="you@company.com"
                   value="{{ old('email') }}" class="fi py-2.5">
            @error('email')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
            <button type="submit"
                    class="inline-flex w-full items-center justify-center rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                Send me a new link
            </button>
        </form>

        <p class="text-sm text-gray-500">
            Already verified?
            <a href="{{ route('login') }}" class="font-semibold text-green-600 hover:text-green-700">Log in</a>
        </p>
    </div>
</x-guest-layout>
