<x-guest-layout>
    <x-slot name="heading">{{ $already ? 'Email already verified' : 'Email verified!' }}</x-slot>
    <x-slot name="subheading">{{ $already ? 'This account was verified earlier — you are good to go.' : 'Your account is now active.' }}</x-slot>

    <div class="text-center space-y-6">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <p class="text-sm text-gray-600">
            <span class="font-semibold">{{ $user->email }}</span> is confirmed.
        </p>

        {{-- Always via login — the email link never grants a session
             (the verifying session is invalidated in the controller). --}}
        <a href="{{ route('login') }}"
           class="inline-flex w-full items-center justify-center rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
            Log in
        </a>
    </div>
</x-guest-layout>
