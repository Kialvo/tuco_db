<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status');
        $search = $request->get('search');

        $query = Order::with(['user', 'items.website'])
            ->whereNotIn('status', [Order::STATUS_DRAFT])
            ->latest('submitted_at');

        if ($status && in_array($status, Order::STATUSES, true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->paginate(25)->withQueryString();

        return view('admin.orders.index', compact('orders', 'status', 'search'));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items.website.country']);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', Order::STATUSES),
        ]);

        $order->update(['status' => $validated['status']]);

        return back()->with('status', "Order {$order->reference} marked as " . Order::STATUS_LABELS[$validated['status']]);
    }
}
