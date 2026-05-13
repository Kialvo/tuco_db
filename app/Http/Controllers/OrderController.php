<?php

namespace App\Http\Controllers;

use App\Mail\OrderSubmittedAdminMail;
use App\Mail\OrderSubmittedCustomerMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class OrderController extends Controller
{
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

        $order->load('items.website.country');

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
            'website_id'   => $website->id,
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

        $user  = auth()->user();
        $order = $user->draftOrder()->load('items.website.country');

        if ($order->items->isEmpty()) {
            return response()->json([
                'error' => 'Your cart is empty.',
            ], 422);
        }

        DB::transaction(function () use ($order, $validated) {
            // Re-snap each item's price defensively in case the website price changed
            foreach ($order->items as $item) {
                $item->refreshPrice();
                $item->save();
            }
            $order->update([
                'status'       => Order::STATUS_SUBMITTED,
                'notes'        => $validated['notes'] ?? null,
                'submitted_at' => now(),
            ]);
        });

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

        return response()->json([
            'status'   => 'success',
            'order_id' => $order->id,
            'redirect' => route('orders.show', $order->id),
        ]);
    }

    /**
     * Build the JSON payload the frontend cart drawer needs.
     */
    private function cartPayload(Order $order): array
    {
        return [
            'id'    => $order->id,
            'count' => $order->items->count(),
            'total' => round($order->items->sum('unit_price'), 2),
            'items' => $order->items->map(function (OrderItem $item) {
                $w = $item->website;
                return [
                    'id'                 => $item->id,
                    'website_id'         => $w->id,
                    'domain'             => $w->domain_name,
                    'country'            => optional($w->country)->country_name,
                    'da'                 => $w->DA,
                    'ms'                 => $w->ms,
                    'price'              => $w->price ? (float) $w->price : null,
                    'sensitive_price'    => $w->sensitive_topic_price ? (float) $w->sensitive_topic_price : null,
                    'has_sensitive'      => ! empty($w->sensitive_topic_price),
                    'article_type'       => $item->article_type,
                    'unit_price'         => (float) $item->unit_price,
                ];
            })->values(),
        ];
    }
}
