<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Thanks for signing up! Before getting started, please verify your email address by clicking the link we just emailed to you. If you didn't receive it, we'll gladly send you another.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" id="resendForm">
        @csrf

        <button type="submit"
                id="resendBtn"
                class="mt-2 w-full inline-flex justify-center items-center px-4 py-2.5 bg-cyan-600 hover:bg-cyan-500 active:bg-cyan-700 disabled:bg-gray-400 disabled:cursor-not-allowed border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-wider shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
            <span id="resendBtnLabel">Resend verification email</span>
        </button>

        <p class="mt-3 text-center text-xs text-gray-500 dark:text-gray-400">
            Didn't get the email? Check your spam folder, or wait before resending.
        </p>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button type="submit"
                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 underline">
            Log out
        </button>
    </form>

    <script>
        (function () {
            const COOLDOWN_SECONDS = 60;
            const STORAGE_KEY = 'verifyEmailCooldownUntil';

            const form = document.getElementById('resendForm');
            const btn = document.getElementById('resendBtn');
            const label = document.getElementById('resendBtnLabel');

            let timerId = null;

            const startCountdown = (until) => {
                if (timerId) clearInterval(timerId);

                const tick = () => {
                    const remaining = Math.ceil((until - Date.now()) / 1000);
                    if (remaining <= 0) {
                        btn.disabled = false;
                        label.textContent = 'Resend verification email';
                        sessionStorage.removeItem(STORAGE_KEY);
                        clearInterval(timerId);
                        return;
                    }
                    btn.disabled = true;
                    label.textContent = `Resend in ${remaining}s`;
                };

                tick();
                timerId = setInterval(tick, 1000);
            };

            // Resume countdown on reload if still active
            const stored = parseInt(sessionStorage.getItem(STORAGE_KEY), 10);
            if (stored && stored > Date.now()) {
                startCountdown(stored);
            }

            // Page just rendered the "verification-link-sent" status — start the cooldown
            @if (session('status') == 'verification-link-sent')
                {
                    const until = Date.now() + COOLDOWN_SECONDS * 1000;
                    sessionStorage.setItem(STORAGE_KEY, until.toString());
                    startCountdown(until);
                }
            @endif

            // Block submission while disabled
            form.addEventListener('submit', (e) => {
                if (btn.disabled) {
                    e.preventDefault();
                }
            });
        })();
    </script>
</x-guest-layout>
