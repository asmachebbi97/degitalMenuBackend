<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function index($restaurantId)
    {
        return response()->json(
            MenuItem::where('restaurant_id', $restaurantId)->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'image' => 'required|string',
            'category' => 'required|string'
        ]);

        $menuItem = MenuItem::create([
            ...$validated,
            'is_available' => true
        ]);

        return response()->json($menuItem, 201);
    }

    public function update(Request $request, $id)
    {
        $menuItem = MenuItem::findOrFail($id);
        $validated = $request->validate([
            'name' => 'string',
            'description' => 'string',
            'price' => 'numeric',
            'image' => 'string',
            'category' => 'string',
            'is_available' => 'boolean'
        ]);

        $menuItem->update($validated);
        return response()->json($menuItem);
    }

    public function destroy($id)
    {
        MenuItem::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
