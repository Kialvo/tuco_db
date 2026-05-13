<x-guest-layout>
    <x-slot name="heading">Reset password</x-slot>
    <x-slot name="subheading">We'll email you a link to choose a new one</x-slot>

    @if (session('status'))
        <div class="mb-4 px-3 py-2 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4" id="forgotForm">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input id="email" name="email" type="email" required autofocus
                   value="{{ old('email') }}" placeholder="you@company.com"
                   class="fi py-2.5">
            @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" id="forgotBtn"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <span id="forgotBtnLabel">Email password reset link</span>
        </button>

        <p class="text-center text-xs text-gray-400">
            Didn't get the email? Check your spam folder, or wait before resending.
        </p>
    </form>

    <p class="mt-8 text-center text-sm text-gray-500">
        Remembered it?
        <a href="{{ route('login') }}"
           class="group ms-1 inline-flex items-center font-semibold text-green-600 hover:text-green-700">
            Back to login
            <x-icon name="arrow-right" size="sm" class="ms-1 transition-transform group-hover:translate-x-0.5" />
        </a>
    </p>

    <script>
        (function () {
            const COOLDOWN_SECONDS = 60;
            const KEY = 'forgotPasswordCooldownUntil';
            const form  = document.getElementById('forgotForm');
            const btn   = document.getElementById('forgotBtn');
            const label = document.getElementById('forgotBtnLabel');
            let timer = null;

            const start = (until) => {
                if (timer) clearInterval(timer);
                const tick = () => {
                    const r = Math.ceil((until - Date.now()) / 1000);
                    if (r <= 0) {
                        btn.disabled = false;
                        label.textContent = 'Email password reset link';
                        sessionStorage.removeItem(KEY);
                        clearInterval(timer);
                        return;
                    }
                    btn.disabled = true;
                    label.textContent = `Resend in ${r}s`;
                };
                tick(); timer = setInterval(tick, 1000);
            };

            const stored = parseInt(sessionStorage.getItem(KEY), 10);
            if (stored && stored > Date.now()) start(stored);

            @if (session('status'))
                {
                    const until = Date.now() + COOLDOWN_SECONDS * 1000;
                    sessionStorage.setItem(KEY, until.toString());
                    start(until);
                }
            @endif

            form.addEventListener('submit', (e) => { if (btn.disabled) e.preventDefault(); });
        })();
    </script>
</x-guest-layout>
