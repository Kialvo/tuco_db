{{--
    Stand-in for the hosted gateway checkout. Only reachable while the fake
    driver is active (the controller 404s otherwise).

    Each button POSTs a *signed webhook* to the real webhook route rather than
    crediting anything directly — so clicking through here exercises signature
    verification, event de-duplication and the ledger keys, which are the parts
    that are expensive to get wrong once real money is involved.
--}}
<x-marketplace-layout>
    <x-slot name="title">Test checkout</x-slot>

    <x-slot name="pageHeader">
        <x-ds.page-header title="Checkout" subtitle="Simulated gateway — no real payment is taken" />
    </x-slot>

    <div class="px-6 py-8">
        <div class="max-w-md mx-auto">

            <div class="bg-white rounded-xl border border-gray-200 shadow-card overflow-hidden">

                <div class="px-5 py-4 bg-amber-50 border-b border-amber-200">
                    <div class="flex items-start gap-2.5">
                        <x-icon name="info" size="sm" class="text-amber-600 mt-0.5 flex-shrink-0" />
                        <div>
                            <p class="text-xs font-semibold text-amber-700">Test mode</p>
                            <p class="text-xs text-amber-700 leading-relaxed mt-0.5">
                                Stands in for the payment provider. Each action sends a signed webhook
                                to the application exactly as the real gateway would.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-5 py-5 space-y-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Package</p>
                        <p class="text-base font-bold text-gray-800 mt-1">{{ ucfirst($purchase->package_key) }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">You receive</p>
                            <p class="text-sm font-semibold text-gray-800 mt-1">
                                {{ number_format($purchase->totalTokens()) }} tokens
                            </p>
                            @if($purchase->bonus_tokens > 0)
                                <p class="text-xs text-green-700 mt-0.5">
                                    {{ number_format($purchase->tokens) }} + {{ $purchase->bonus_tokens }} bonus
                                </p>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">You pay</p>
                            <p class="text-sm font-semibold text-gray-800 mt-1">
                                {{ $purchase->currency }} {{ number_format($purchase->amount_minor / 100, 2) }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 font-mono break-all pt-3 border-t border-gray-100">
                        {{ $session }}
                    </p>
                </div>

                <div class="px-5 py-4 bg-gray-50 border-t border-gray-200 space-y-2">
                    <form method="POST" action="{{ route('billing.fake-checkout.pay', ['session' => $session]) }}">
                        @csrf
                        <x-ds.button type="submit" variant="primary" size="lg" block>
                            Pay {{ $purchase->currency }} {{ number_format($purchase->amount_minor / 100, 2) }}
                        </x-ds.button>
                    </form>

                    <form method="POST" action="{{ route('billing.fake-checkout.fail', ['session' => $session]) }}">
                        @csrf
                        <x-ds.button type="submit" variant="secondary" size="sm" block>
                            Simulate a failed payment
                        </x-ds.button>
                    </form>

                    @if($purchase->status === \App\Models\TokenPurchase::STATUS_PAID)
                        <form method="POST" action="{{ route('billing.fake-checkout.refund', ['session' => $session]) }}">
                            @csrf
                            <x-ds.button type="submit" variant="danger" size="sm" block>
                                Simulate a refund
                            </x-ds.button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('billing.tokens.index') }}" class="text-xs text-gray-500 hover:text-gray-600">
                    Cancel and go back
                </a>
            </div>

        </div>
    </div>

</x-marketplace-layout>
