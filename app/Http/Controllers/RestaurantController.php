<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'image' => 'nullable|string',
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

    public function destroy($id)
    {
        Restaurant::findOrFail($id)->delete();
        return response()->json(null, 200);
    }

    public function toggleActive($id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $restaurant->is_active = !$restaurant->is_active;
        $restaurant->save();
        return response()->json($restaurant);
    }

    public function toggleAvailable($id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $restaurant->available = !$restaurant->available;
        $restaurant->save();
        return response()->json($restaurant);
    }

    public function getByOwner($owner_id)
    {
        return response()->json(
            Restaurant::where('owner_id', $owner_id)->get()
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

 

public function getStatisticsByOwner($ownerId)
{
    $restaurants = Restaurant::where('owner_id', $ownerId)->get();

    $totalOrders = 0;
    $totalRevenue = 0;
    $totalCustomers = collect();
    $restaurantStats = [];

    foreach ($restaurants as $restaurant) {
        $orders = $restaurant->orders()->get(); // Fetch actual orders
        $customerIds = $orders->pluck('customer_id')->unique(); // Unique per restaurant
        $ordersCount = $orders->count();
        $revenueSum = $orders->sum('total_amount');
        $customersCount = $orders->pluck('customer_id')->unique(); // Unique customer IDs for this restaurant
        $satisfaction = 0; // Placeholder

        $restaurantStats[] = [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'orders' => $ordersCount,
            'revenue' => $revenueSum,
            'customers' => $customerIds->count(),
            'satisfaction' => round($satisfaction, 1),
        ];


        
        $totalOrders += $ordersCount;
        $totalRevenue += $revenueSum;
        $totalCustomers = $totalCustomers->merge($customersCount);
    }

    $uniqueCustomers = $totalCustomers->unique()->count();
    $averageOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

    $trends = [
        'customers' => ['value' => 15.2, 'isPositive' => true],
        'orders' => ['value' => 12.8, 'isPositive' => true],
        'revenue' => ['value' => 18.4, 'isPositive' => true],
        'satisfaction' => ['value' => 3.2, 'isPositive' => true]
    ];

    $allRestaurantIds = $restaurants->pluck('id');

    $ordersQuery = DB::table('orders')->whereIn('restaurant_id', $allRestaurantIds);

    $weeklyData = (clone $ordersQuery)
        ->whereDate('created_at', '>=', now()->subDays(7))
        ->selectRaw('DAYNAME(created_at) as name, COUNT(*) as orders, SUM(total_amount) as revenue, COUNT(DISTINCT customer_id) as customers')
        ->groupBy(DB::raw('DAYNAME(created_at)'))
        ->orderByRaw("FIELD(DAYNAME(created_at), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
        ->get();

    $monthlySales = (clone $ordersQuery)
        ->whereYear('created_at', now()->year)
        ->selectRaw('MONTHNAME(created_at) as name, SUM(total_amount) as revenue, COUNT(*) as orders, COUNT(DISTINCT customer_id) as customers')
        ->groupBy(DB::raw('MONTH(created_at)'))
        ->orderByRaw("FIELD(MONTHNAME(created_at), 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')")
        ->get();

    $yearlyData = (clone $ordersQuery)
        ->selectRaw('YEAR(created_at) as name, SUM(total_amount) as revenue, COUNT(*) as orders, COUNT(DISTINCT customer_id) as customers')
        ->groupBy(DB::raw('YEAR(created_at)'))
        ->orderBy('name')
        ->get();

    $categoryBreakdown = DB::table('order_items')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
        ->whereIn('orders.restaurant_id', $allRestaurantIds)
        ->selectRaw('menu_items.category as name, COUNT(*) as value, SUM(order_items.quantity * menu_items.price) as revenue')
        ->groupBy('menu_items.category')
        ->get();

    $peakHoursByDay = DB::table('orders')
        ->whereIn('restaurant_id', $allRestaurantIds)
        ->selectRaw("DAYNAME(created_at) as day, HOUR(created_at) as hour, COUNT(*) as orders")
        ->groupBy(DB::raw("DAYOFWEEK(created_at), HOUR(created_at)"))
        ->orderByRaw("FIELD(DAYNAME(created_at), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
        ->orderBy('hour')
        ->get();

    return response()->json([
        'totalRestaurants' => $restaurants->count(),
        'totalCustomers' => $uniqueCustomers,
        'totalOrders' => $totalOrders,
        'totalRevenue' => round($totalRevenue, 2),
        'averageOrderValue' => $averageOrderValue,
        'customerSatisfaction' => round($restaurantStats ? collect($restaurantStats)->avg('satisfaction') : 0, 1),
        'trends' => $trends,
        'restaurants' => $restaurantStats,
        'weeklyData' => $weeklyData,
        'monthlySales' => $monthlySales,
        'yearlyData' => $yearlyData,
        'categoryBreakdown' => $categoryBreakdown,
        'peakHoursByDay' => $peakHoursByDay
    ]);
}


public function getAllRestaurantsStatistics()
{
    $restaurants = Restaurant::with('orders')->get();

    $totalRestaurants = $restaurants->count();
    $totalOrders = 0;
    $totalRevenue = 0;
    $trendsMap = [];
    $topRestaurants = [];

    foreach ($restaurants as $restaurant) {
        $orders = $restaurant->orders;

        $orderCount = $orders->count();
        $revenue = $orders->sum('total_amount');
        $customerCount = $orders->pluck('customer_id')->unique()->count();

        // Aggregate revenue per day (for trends)
        foreach ($orders as $order) {
            $date = $order->created_at->toDateString();
            if (!isset($trendsMap[$date])) {
                $trendsMap[$date] = 0;
            }
            $trendsMap[$date] += $order->total_amount;
        }

        // Build top restaurant entry
        $topRestaurants[] = [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'orders' => $orderCount,
            'revenue' => $revenue,
            'customers' => $customerCount,
        ];

        $totalOrders += $orderCount;
        $totalRevenue += $revenue;
    }

    // Sort trends by date
    ksort($trendsMap);
    $trends = collect($trendsMap)->map(function ($revenue, $date) {
        return ['date' => $date, 'revenue' => $revenue];
    })->values();

    // Get top 5 performing restaurants by revenue
    $topRestaurants = collect($topRestaurants)
        ->sortByDesc('totalRevenue')
        ->take(5)
        ->values();

    return response()->json([
        'totalRestaurants' => $totalRestaurants,
        'totalOrders' => $totalOrders,
        'totalRevenue' => $totalRevenue,
        'trends' => $trends,
        'topRestaurants' => $topRestaurants,
    ]);
}


}
