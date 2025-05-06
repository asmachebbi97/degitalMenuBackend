<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Models\MenuItem;

class OrderController extends Controller
{
    public function index()
    {
        return response()->json(
            Order::with(['items', 'restaurant'])->get()
        );
    }

    public function getByRestaurant($restaurantId)
    {
        return response()->json(
            Order::with(['items', 'customer'])
                ->where('restaurant_id', $restaurantId)
                ->get()
        );
    }

    public function getByCustomer($customerId)
    {
        return response()->json(
            Order::with(['items', 'restaurant'])
                ->where('customer_id', $customerId)
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'items' => 'required|array',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        $totalAmount = 0;
        foreach ($validated['items'] as $item) {
            $menuItem = MenuItem::findOrFail($item['menu_item_id']);
            $totalAmount += $menuItem->price * $item['quantity'];
        }

        $order = Order::create([
            'customer_id' => $request->user()->id,
            'restaurant_id' => $validated['restaurant_id'],
            'status' => 'pending',
            'total_amount' => $totalAmount
        ]);

        foreach ($validated['items'] as $item) {
            $menuItem = MenuItem::findOrFail($item['menu_item_id']);
            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'price' => $menuItem->price,
                'quantity' => $item['quantity']
            ]);
        }

        return response()->json(
            Order::with(['items', 'restaurant'])->find($order->id),
            201
        );
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,delivered,cancelled'
        ]);

        $order = Order::with('items')->findOrFail($id);
        $order->update(['status' => $validated['status']]);

        return response()->json($order);
    }
}
