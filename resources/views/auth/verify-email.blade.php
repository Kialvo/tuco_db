<x-guest-layout>
    <x-slot name="heading">Verify your email</x-slot>
    <x-slot name="subheading">We sent a link to your inbox</x-slot>

    <p class="text-sm text-gray-600 leading-relaxed mb-4">
        Thanks for signing up! Before getting started, please verify your email address by clicking the link we just sent. If you didn't get it, we'll gladly send another.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 px-3 py-2 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" id="resendForm" class="space-y-3">
        @csrf
        {{-- Disabled state comes from the SERVER ($secondsLeft, per-user rate
             limiter): registration counts as the first send, so this lands
             greyed out. The JS below only animates the number — early POSTs
             are rejected server-side regardless. --}}
        <button type="submit" id="resendBtn" @if(($secondsLeft ?? 0) > 0) disabled @endif
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <span id="resendBtnLabel">Resend verification email</span>
        </button>
        <p class="text-center text-xs text-gray-400">
            Didn't get the email? Check your spam folder, or wait before resending.
        </p>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 underline">
            Log out
        </button>
    </form>

    <script>
        (function () {
            // Countdown seconds come from the server's rate limiter — the
            // single source of truth. No sessionStorage: a new tab or
            // incognito window shows exactly the same remaining time.
            const form  = document.getElementById('resendForm');
            const btn   = document.getElementById('resendBtn');
            const label = document.getElementById('resendBtnLabel');
            let remaining = {{ (int) ($secondsLeft ?? 0) }};

            if (remaining > 0) {
                const tick = () => {
                    if (remaining <= 0) {
                        btn.disabled = false;
                        label.textContent = 'Resend verification email';
                        clearInterval(t);
                        return;
                    }
                    btn.disabled = true;
                    label.textContent = `Resend in ${remaining}s`;
                    remaining--;
                };
                tick();
                const t = setInterval(tick, 1000);
            }

            form.addEventListener('submit', (e) => { if (btn.disabled) e.preventDefault(); });
        })();
    </script>
</x-guest-layout>
