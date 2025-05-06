<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function index()
    {
        return response()->json(Restaurant::with('owner')->get());
    }

    public function show($id)
    {
        return response()->json(
            Restaurant::with(['owner', 'menuItems'])->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'image' => 'required|string',
            'cuisine' => 'required|string'
        ]);

        $restaurant = Restaurant::create([
            'owner_id' => $request->user()->id,
            ...$validated,
            'is_active' => true
        ]);

        return response()->json($restaurant, 201);
    }

    public function update(Request $request, $id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $validated = $request->validate([
            'name' => 'string',
            'description' => 'string',
            'address' => 'string',
            'phone' => 'string',
            'image' => 'string',
            'cuisine' => 'string'
        ]);

        $restaurant->update($validated);
        return response()->json($restaurant);
    }

    public function toggleActive($id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $restaurant->is_active = !$restaurant->is_active;
        $restaurant->save();
        return response()->json($restaurant);
    }

    public function getByOwner($ownerId)
    {
        return response()->json(
            Restaurant::where('owner_id', $ownerId)->get()
        );
    }

    public function getStatistics($id)
{
    $restaurant = Restaurant::findOrFail($id);

    $totalOrders = $restaurant->orders()->count();
    $totalRevenue = $restaurant->orders()->sum('total_amount');
    $totalCustomers = $restaurant->orders()->distinct('customer_id')->count('customer_id');

    // Example trend: revenue per day (last 7 days)
    $trends = $restaurant->orders()
        ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
        ->where('created_at', '>=', now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return response()->json([
            'restaurant_id' => $restaurant->id,
            'restaurant_name' => $restaurant->name,
            'totalCustomers' => $totalCustomers,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'trends' => $trends,
        ]);
}

}
