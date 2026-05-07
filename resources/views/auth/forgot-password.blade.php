<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Forgot your password? No problem. Enter your email and we'll send you a link to choose a new one.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" id="forgotForm">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit"
                id="forgotBtn"
                class="mt-6 w-full inline-flex justify-center items-center px-4 py-2.5 bg-cyan-600 hover:bg-cyan-500 active:bg-cyan-700 disabled:bg-gray-400 disabled:cursor-not-allowed border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-wider shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
            <span id="forgotBtnLabel">Email password reset link</span>
        </button>

        <p class="mt-3 text-center text-xs text-gray-500 dark:text-gray-400">
            Didn't get the email? Check your spam folder, or wait before resending.
        </p>
    </form>

    <p class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400">
        Remembered it?
        <a href="{{ route('login') }}"
           class="group ms-1 inline-flex items-center font-semibold text-cyan-600 hover:text-cyan-500 dark:text-cyan-400 dark:hover:text-cyan-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 dark:focus:ring-offset-gray-800 rounded">
            Back to login
            <svg class="ms-1 h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10.293 4.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L13.586 11H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </a>
    </p>

    <script>
        (function () {
            const COOLDOWN_SECONDS = 60;
            const STORAGE_KEY = 'forgotPasswordCooldownUntil';

            const form = document.getElementById('forgotForm');
            const btn = document.getElementById('forgotBtn');
            const label = document.getElementById('forgotBtnLabel');

            let timerId = null;

            const startCountdown = (until) => {
                if (timerId) clearInterval(timerId);

                const tick = () => {
                    const remaining = Math.ceil((until - Date.now()) / 1000);
                    if (remaining <= 0) {
                        btn.disabled = false;
                        label.textContent = 'Email password reset link';
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

            const stored = parseInt(sessionStorage.getItem(STORAGE_KEY), 10);
            if (stored && stored > Date.now()) {
                startCountdown(stored);
            }

            @if (session('status'))
                {
                    const until = Date.now() + COOLDOWN_SECONDS * 1000;
                    sessionStorage.setItem(STORAGE_KEY, until.toString());
                    startCountdown(until);
                }
            @endif

            form.addEventListener('submit', (e) => {
                if (btn.disabled) {
                    e.preventDefault();
                }
            });
        })();
    </script>
</x-guest-layout>
