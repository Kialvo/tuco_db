<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientTokens;
use App\Mail\OrderSubmittedAdminMail;
use App\Mail\OrderSubmittedCustomerMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Website;
use App\Services\NotificationHub;
use App\Services\Tokens\SpendingPolicy;
use App\Services\Tokens\TokenLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly TokenLedger $ledger) {}

    /**
     * List the current user's submitted orders.
     */
    public function index()
    {
        $orders = Order::with('items.website.country')
            ->forUser(auth()->id())
            ->submitted()
            ->latest('submitted_at')
            ->paginate(20);

        return view('marketplace.orders.index', compact('orders'));
    }

    /**
     * Show a single order belonging to the current user.
     */
    public function show(Order $order): View|RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);

        if ($order->status === Order::STATUS_DRAFT) {
            return redirect()->route('websites.index');
        }

        // statusEvents dates the tracker's steps; without it the view would
        // query them once per row.
        $order->load('items.website.country', 'items.publication.statusEvents');

        return view('marketplace.orders.show', compact('order'));
    }

    /**
     * Return the current user's draft order (cart) as JSON.
     */
    public function cart(): JsonResponse
    {
        $order = auth()->user()->draftOrder();
        $order->load('items.website.country');

        return response()->json($this->cartPayload($order));
    }

    /**
     * Add a website to the cart with default article_type=standard.
     */
    public function addItem(Request $request, Website $website): JsonResponse
    {
        $order = auth()->user()->draftOrder();

        $existing = $order->items()->where('website_id', $website->id)->first();
        if ($existing) {
            return response()->json($this->cartPayload($order->fresh('items.website.country')));
        }

        $item = new OrderItem([
            'website_id' => $website->id,
            'article_type' => OrderItem::TYPE_STANDARD,
        ]);
        $item->order_id = $order->id;
        $item->setRelation('website', $website);
        $item->refreshPrice();
        $item->save();

        return response()->json($this->cartPayload($order->fresh('items.website.country')));
    }

    /**
     * Remove an item from the current user's cart.
     */
    public function removeItem(OrderItem $item): JsonResponse
    {
        $order = $item->order;
        abort_unless(
            $order && $order->user_id === auth()->id() && $order->status === Order::STATUS_DRAFT,
            403
        );

        $item->delete();

        return response()->json($this->cartPayload($order->fresh('items.website.country')));
    }

    /**
     * Set article_type (standard | sensitive) for an item and re-snap the unit_price.
     */
    public function setArticleType(Request $request, OrderItem $item): JsonResponse
    {
        $order = $item->order;
        abort_unless(
            $order && $order->user_id === auth()->id() && $order->status === Order::STATUS_DRAFT,
            403
        );

        $validated = $request->validate([
            'article_type' => 'required|in:standard,sensitive',
        ]);

        $item->load('website');

        if ($validated['article_type'] === OrderItem::TYPE_SENSITIVE && empty($item->website?->sensitive_topic_price)) {
            return response()->json([
                'error' => 'This domain does not accept sensitive content.',
            ], 422);
        }

        $item->article_type = $validated['article_type'];
        $item->refreshPrice();
        $item->save();

        return response()->json($this->cartPayload($order->fresh('items.website.country')));
    }

    /**
     * Submit the draft order: status=submitted, send notifications.
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        $user = auth()->user();
        $order = $user->draftOrder()->load('items.website.country');

        if ($order->items->isEmpty()) {
            return response()->json([
                'error' => 'Your cart is empty.',
            ], 422);
        }

        // Tokens are HELD here, not spent. They become revenue only when the
        // article is published (Fabrizio, 2026-09-09), and a site that falls
        // through releases its own tokens without touching the rest of the
        // order. Held per item for exactly that reason.
        //
        // The whole thing sits in one transaction: a partially-held order —
        // some sites committed, some not, status already 'submitted' — would
        // be worse than a rejected one, because nothing downstream would know.
        try {
            DB::transaction(function () use ($order, $validated, $user) {
                // Re-snap each item's price defensively in case the website price changed
                foreach ($order->items as $item) {
                    $item->refreshPrice();
                    $item->save();
                }

                // Gated: with spending off, submission behaves exactly as it
                // always has. Switching the marketplace to prepaid is an
                // announced product change, not something a deploy does by
                // itself — see config/tokens.php.
                if (SpendingPolicy::appliesTo($user)) {
                    $this->ledger->holdForOrder(
                        $this->ledger->accountFor($user),
                        $order->load('items'),
                        $user,
                    );
                }

                $order->update([
                    'status' => Order::STATUS_SUBMITTED,
                    'notes' => $validated['notes'] ?? null,
                    'submitted_at' => now(),
                ]);
            });
        } catch (InsufficientTokens $e) {
            // 422 rather than a redirect: the drawer shows the shortfall inline
            // with a Buy tokens CTA. The client-side gate is a courtesy; THIS
            // is the one that actually protects the balance.
            return response()->json([
                'error' => 'Not enough tokens for this order.',
                'balance' => $e->balance,
                'required' => $e->required,
                'missing' => $e->shortfall(),
            ], 422);
        }

        // Open the campaign + one publication per site so the order becomes
        // trackable work for Martina, and the customer's progress view has
        // something real behind it. Deliberately outside the transaction
        // above and internally non-fatal: bookkeeping must never cost us a
        // submitted order.
        app(\App\Services\MarketplaceOrderFulfilment::class)->fulfil($order);

        try {
            Mail::to($order->user->email)->send(new OrderSubmittedCustomerMail($order));
        } catch (\Throwable $e) {
            Log::error('Order customer email failed: '.$e->getMessage());
        }

        try {
            Mail::to('networkmenford@gmail.com')->send(new OrderSubmittedAdminMail($order));
        } catch (\Throwable $e) {
            Log::error('Order admin email failed: '.$e->getMessage());
        }

        NotificationHub::orderSubmitted($order);

        return response()->json([
            'status' => 'success',
            'order_id' => $order->id,
            'redirect' => route('orders.show', $order->id),
        ]);
    }

    /**
     * Build the JSON payload the frontend cart drawer needs.
     */
    private function cartPayload(Order $order): array
    {
        $user = $order->user ?? auth()->user();
        $spends = SpendingPolicy::appliesTo($user);
        $cost = $order->items->sum(fn (OrderItem $item) => $item->tokenCost());

        // Resolved ONLY when this user actually spends tokens. accountFor()
        // creates a team and a wallet on first call, and the cart is loaded on
        // every page carrying the drawer — so doing it unconditionally would
        // have every browsing guest generating rows for a feature that is
        // switched off. Dormant has to mean dormant.
        $balance = 0;
        $held = 0;

        if ($spends && $user) {
            // The balance is what is SPENDABLE — holds are already debited out
            // of it — so `held` is reported alongside, or an agency with a
            // large order in flight sees a balance that looks as if it vanished.
            $account = $this->ledger->accountFor($user);
            $balance = (int) $account->balance_cached;
            $held = $this->ledger->heldTotal($account);
        }

        return [
            'id' => $order->id,
            'count' => $order->items->count(),
            'total' => round($order->items->sum('unit_price'), 2),
            'tokens_required' => $cost,
            'balance' => $balance,
            'held' => $held,
            'balance_after' => $balance - $cost,
            'has_enough' => $balance >= $cost,
            'missing' => max(0, $cost - $balance),
            'spending_enabled' => $spends,
            'items' => $order->items->map(function (OrderItem $item) {
                $w = $item->website;

                return [
                    'id' => $item->id,
                    'website_id' => $w->id,
                    'domain' => $w->domain_name,
                    'country' => optional($w->country)->country_name,
                    'da' => $w->DA,
                    'ms' => $w->ms,
                    'price' => $w->price ? (float) $w->price : null,
                    'sensitive_price' => $w->sensitive_topic_price ? (float) $w->sensitive_topic_price : null,
                    'has_sensitive' => ! empty($w->sensitive_topic_price),
                    'article_type' => $item->article_type,
                    'unit_price' => (float) $item->unit_price,
                ];
            })->values(),
        ];
    }
}
