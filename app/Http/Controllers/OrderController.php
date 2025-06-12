<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Models\MenuItem;
use App\Models\History;
use App\Models\Delivery;
use App\Models\Cart;
use App\Models\CartItem;
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

    public function updatePaymentStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:pending,paid,failed'
        ]);

        $order = Order::findOrFail($id);
        $order->update(['payment_status' => $validated['payment_status']]);

        return response()->json($order);
    }



    //Cart 

    public function addToCart(Request $request)
{
    $validated = $request->validate([
        'restaurant_id' => 'required|exists:restaurants,id',
        'items' => 'required|array',
        'items.*.menu_item_id' => 'required|exists:menu_items,id',
        'items.*.quantity' => 'required|integer|min:1'
    ]);

    $user = $request->user();

    // Find or create the user's active cart
    $cart = Cart::firstOrCreate([
        'user_id' => $user->id,
        'restaurant_id' => $validated['restaurant_id'],
        'status' => 'active', // optional status tracking
    ]);

    foreach ($validated['items'] as $item) {
        $menuItem = MenuItem::findOrFail($item['menu_item_id']);

        // Check if the item already exists in the cart
        $cartItem = $cart->items()->where('menu_item_id', $menuItem->id)->first();

        if ($cartItem) {
            // Update quantity if exists
            $cartItem->quantity += $item['quantity'];
            $cartItem->save();
        } else {
            // Create new cart item
            $cart->items()->create([
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'price' => $menuItem->price,
                'quantity' => $item['quantity']
            ]);
        }
    }

    return response()->json(
        $cart->load(['items.menuItem']), // load related items + menuItem details
        200
    );
}

public function updateCartItem(Request $request, $itemId)
{
    $validated = $request->validate([
        'quantity' => 'required|integer|min:1'
    ]);

    $cartItem = CartItem::findOrFail($itemId);

    if ($cartItem->cart->user_id !== $request->user()->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $cartItem->update(['quantity' => $validated['quantity']]);

    return response()->json($cartItem->fresh(), 200);
}


public function deleteCartItem(Request $request, $itemId)
{
    $cartItem = CartItem::findOrFail($itemId);

    if ($cartItem->cart->user_id !== $request->user()->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $cartItem->delete();

    return response()->json(['message' => 'Item removed from cart'], 200);
}

public function changeCartStatus(Request $request, $cartId)
{
    $validated = $request->validate([
        'status' => 'required|in:active,checked_out,abandoned'
    ]);

    $cart = Cart::findOrFail($cartId);

    if ($cart->user_id !== $request->user()->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $cart->update(['status' => $validated['status']]);

    return response()->json(['message' => 'Cart status updated', 'cart' => $cart], 200);
}



public function clearCart(Request $request)
{
    $user = $request->user();

    $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();

    if (!$cart) {
        return response()->json(['message' => 'Cart not found'], 404);
    }

    $cart->items()->delete();

    return response()->json(['message' => 'Cart cleared'], 200);
}


//History

public function addOrderHistory(Request $request)
{
 
    $history = History::create([
        'order_id' => $request->order_id,
        'status' => $request->status,
        'note' => $request->note,
    ]);
    return response()->json($history, 201);
}


public function addDeliveryInfo(Request $request, $orderId)
{
    $validated = $request->validate([
        'address' => 'required|string',
        'city' => 'required|string',
        'postal_code' => 'nullable|string',
        'instructions' => 'nullable|string'
    ]);

    $order = Order::findOrFail($orderId);

    if ($order->customer_id !== $request->user()->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $delivery = Delivery::create([
        'order_id' => $order->id,
        'address' => $validated['address'],
        'city' => $validated['city'],
        'postal_code' => $validated['postal_code'],
        'instructions' => $validated['instructions'] ?? null
    ]);

    return response()->json($delivery, 201);
}


}
