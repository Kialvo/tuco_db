{{--
    Team / shared balance.

    The balance belongs to the team, so this page answers one question: who is
    allowed to spend it. Everything an owner can do here is irreversible from
    the member's point of view, so each destructive action states its effect in
    words rather than relying on an icon.

    Built from the app's design-system components so it matches the wallet it
    sits beside.
--}}
<x-marketplace-layout>
    <x-slot name="title">Team</x-slot>

    <x-slot name="pageHeader">
        <x-ds.page-header title="Team" subtitle="Everyone here shares one token balance.">
            <x-slot name="actions">
                <x-ds.button :href="route('billing.tokens.index')" variant="secondary" size="md">
                    <x-icon name="arrow-left" size="sm" /> Back to tokens
                </x-ds.button>
            </x-slot>
        </x-ds.page-header>
    </x-slot>

    <div class="px-6 py-5 space-y-6 max-w-4xl">

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

        {{-- ─────────── The shared balance ─────────── --}}
        <section data-card="static" class="bg-white rounded-xl border border-gray-200 shadow-card p-6">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $team->name }}</h2>

            <div class="flex items-baseline gap-2.5 mt-3">
                <span class="text-3xl font-bold text-gray-800 leading-none tabular-nums">{{ number_format($balance) }}</span>
                <span class="text-sm font-semibold text-gray-500">tokens available</span>
            </div>

            @if($held > 0)
                {{-- Already out of the figure above, so it is worded as an
                     explanation rather than a second subtraction. --}}
                <p class="text-sm text-gray-500 mt-2 tabular-nums">
                    <span class="font-semibold text-gray-600">{{ number_format($held) }}</span>
                    on hold for orders in progress
                </p>
            @endif

            <p class="text-sm text-gray-600 leading-relaxed mt-5 pt-4 border-t border-gray-100">
                Everyone on this team can see this balance and spend it. If you need someone to
                prepare orders without placing them, ask them to send you their selection first —
                there is no separate permission for that.
            </p>
        </section>

        {{-- ─────────── Members ─────────── --}}
        <section aria-labelledby="membersHeading">
            <h2 id="membersHeading" class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                Members ({{ $members->count() }})
            </h2>

            <div class="bg-white rounded-xl border border-gray-200 shadow-card overflow-hidden">
                <ul class="divide-y divide-gray-100">
                    @foreach($members as $member)
                        <li class="flex items-center justify-between gap-4 px-5 py-3.5">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 truncate">
                                    {{ $member->name }}
                                    @if($team->isOwnedBy($member))
                                        <x-ds.pill tone="green">Owner</x-ds.pill>
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500 truncate">{{ $member->email }}</p>
                            </div>

                            @if($isOwner && ! $team->isOwnedBy($member))
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <form method="POST" action="{{ route('billing.team.transfer') }}"
                                          onsubmit="return confirm('Make {{ $member->name }} the owner? You will no longer be able to invite or remove people.');">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $member->id }}">
                                        <button type="submit"
                                                class="text-sm font-semibold text-blue-700 hover:text-blue-800 underline underline-offset-2">
                                            Make owner
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('billing.team.members.remove', $member) }}"
                                          onsubmit="return confirm('Remove {{ $member->name }}? They will immediately lose access to this balance.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-sm font-semibold text-red-700 hover:text-red-800 underline underline-offset-2">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- ─────────── Pending invitations ─────────── --}}
        @if($invitations->isNotEmpty())
            <section aria-labelledby="invitesHeading">
                <h2 id="invitesHeading" class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                    Pending invitations
                </h2>

                <div class="bg-white rounded-xl border border-gray-200 shadow-card overflow-hidden">
                    <ul class="divide-y divide-gray-100">
                        @foreach($invitations as $invitation)
                            <li class="flex items-center justify-between gap-4 px-5 py-3.5">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-800 truncate">{{ $invitation->email }}</p>
                                    <p class="text-sm text-gray-500">
                                        Expires {{ $invitation->expires_at->format('j M Y') }}
                                    </p>
                                </div>

                                @if($isOwner)
                                    <form method="POST" action="{{ route('billing.team.invitations.revoke', $invitation) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-sm font-semibold text-gray-600 hover:text-gray-800 underline underline-offset-2">
                                            Cancel
                                        </button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        {{-- ─────────── Invite ─────────── --}}
        @if($isOwner)
            <section aria-labelledby="inviteHeading"
                     class="bg-white rounded-xl border border-gray-200 shadow-card p-6">
                <h2 id="inviteHeading" class="text-sm font-semibold text-gray-800">Invite a colleague</h2>
                <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">
                    They will be able to spend this balance as soon as they accept. Nobody can join by
                    entering your company name — an invitation to their email address is the only way in.
                </p>

                <form method="POST" action="{{ route('billing.team.invite') }}"
                      class="flex flex-col sm:flex-row gap-3 mt-4">
                    @csrf
                    <label for="inviteEmail" class="sr-only">Email address</label>
                    <input type="email" name="email" id="inviteEmail" required
                           placeholder="colleague@youragency.com"
                           value="{{ old('email') }}"
                           class="flex-1 rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
                    <button type="submit" data-cta="primary"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-5 py-2.5
                                   text-sm font-semibold text-white hover:bg-green-700 transition-colors
                                   focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Send invitation
                    </button>
                </form>
            </section>
        @else
            <p class="text-sm text-gray-600">
                <span class="font-semibold text-gray-800">{{ $team->owner->name ?? 'The owner' }}</span>
                manages this team. Ask them to invite or remove people.
            </p>
        @endif

    </div>
</x-marketplace-layout>
