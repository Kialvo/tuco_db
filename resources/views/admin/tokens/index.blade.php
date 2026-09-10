{{--
    Manual token adjustments (admin only).

    Money moved by hand, so the form is deliberately unglamorous: every field is
    required, the reason has a minimum length, and the direction is an explicit
    choice rather than a signed number someone can fat-finger.

    A hidden per-render token makes a double-clicked submit resolve to the same
    ledger key, so it books once. See TokenLedger::adjust().
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Token adjustments</h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6 max-w-6xl mx-auto">

        @if(session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">This moves real customer balances.</p>
            <p class="mt-1 leading-relaxed">
                Every adjustment is recorded permanently against your name with the reason you write —
                the ledger is append-only, so a mistake is corrected by a second adjustment, never by
                deleting the first. To fix a stuck <em>hold</em> on an order, use
                <code class="font-mono text-xs">tokens:fix-hold</code> instead.
            </p>
        </div>

        {{-- ─────────── Find a team ─────────── --}}
        <form method="GET" action="{{ route('admin.tokens.index') }}" class="flex gap-3">
            <label for="q" class="sr-only">Search teams</label>
            <input type="search" name="q" id="q" value="{{ $search }}"
                   placeholder="Team name, owner name or email"
                   class="flex-1 rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
            <button type="submit"
                    class="rounded-lg bg-gray-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-900">
                Search
            </button>
        </form>

        {{-- ─────────── Teams ─────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="text-left font-semibold px-5 py-3">Team</th>
                            <th class="text-left font-semibold px-5 py-3">Owner</th>
                            <th class="text-right font-semibold px-5 py-3">Members</th>
                            <th class="text-right font-semibold px-5 py-3">Balance</th>
                            <th class="text-left font-semibold px-5 py-3">Adjust</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($teams as $team)
                            <tr>
                                <td class="px-5 py-3 font-semibold text-gray-800">{{ $team->name }}</td>
                                <td class="px-5 py-3 text-gray-600">
                                    {{ $team->owner->name ?? '—' }}
                                    <span class="block text-xs text-gray-500">{{ $team->owner->email ?? '' }}</span>
                                </td>
                                <td class="px-5 py-3 text-right tabular-nums text-gray-600">{{ $team->members_count }}</td>
                                <td class="px-5 py-3 text-right tabular-nums font-semibold text-gray-800">
                                    {{ number_format((int) ($team->tokenAccount->balance_cached ?? 0)) }}
                                </td>
                                <td class="px-5 py-3">
                                    <form method="POST" action="{{ route('admin.tokens.adjust', $team) }}"
                                          class="flex flex-wrap items-center gap-2"
                                          onsubmit="return confirm('Apply this adjustment to {{ $team->name }}? It cannot be undone, only corrected by another adjustment.');">
                                        @csrf
                                        <input type="hidden" name="form_token" value="{{ $formToken }}">

                                        <label class="sr-only" for="dir{{ $team->id }}">Direction</label>
                                        <select name="direction" id="dir{{ $team->id }}"
                                                class="rounded-lg border-gray-300 text-sm py-1.5">
                                            <option value="credit">Add</option>
                                            <option value="debit">Remove</option>
                                        </select>

                                        <label class="sr-only" for="amt{{ $team->id }}">Tokens</label>
                                        <input type="number" name="tokens" id="amt{{ $team->id }}"
                                               min="1" max="100000" required placeholder="0"
                                               class="w-24 rounded-lg border-gray-300 text-sm py-1.5 tabular-nums">

                                        <label class="sr-only" for="rsn{{ $team->id }}">Reason</label>
                                        <input type="text" name="reason" id="rsn{{ $team->id }}"
                                               required minlength="5" maxlength="255"
                                               placeholder="Reason (recorded permanently)"
                                               class="flex-1 min-w-[14rem] rounded-lg border-gray-300 text-sm py-1.5">

                                        <button type="submit"
                                                class="rounded-lg bg-green-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-green-700">
                                            Apply
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-gray-500">
                                    No teams found{{ $search !== '' ? ' for "'.$search.'"' : '' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $teams->links() }}

        {{-- ─────────── Recent adjustments ─────────── --}}
        @if($recent->isNotEmpty())
            <section>
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                    Last 20 adjustments
                </h2>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <ul class="divide-y divide-gray-100 text-sm">
                        @foreach($recent as $tx)
                            <li class="px-5 py-3 flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-800">
                                        {{ $tx->account->team->name ?? 'Team #'.$tx->account?->team_id }}
                                    </p>
                                    <p class="text-gray-600">{{ $tx->metadata['reason'] ?? '—' }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $tx->created_at?->format('j M Y H:i') }}
                                        @if($tx->created_by)
                                            · by user #{{ $tx->created_by }}
                                        @endif
                                    </p>
                                </div>
                                <span class="tabular-nums font-bold flex-shrink-0
                                             {{ $tx->amount > 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

    </div>
</x-app-layout>
