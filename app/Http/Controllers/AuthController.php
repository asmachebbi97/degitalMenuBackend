<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
{
    try {
        // Validate incoming data
        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'name' => 'required|string',
            'role' => 'required|in:restaurant,customer,admin'
        ]);

        // Create user in the database
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => $validated['role'] === 'customer'
        ]);

        // Check if user is created successfully
        if ($user) {
            // Generate token for customer role
            if ($user->role === 'customer') {
                $token = $user->createToken('auth_token')->plainTextToken;
                return response()->json([
                    'user' => $user,
                    'token' => $token
                ], 201);
            }

            // Return pending approval message for restaurant role
            return response()->json([
                'message' => 'Restaurant account pending approval'
            ], 201);
        }

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Handle validation errors
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Illuminate\Database\QueryException $e) {
        // Handle database-related errors (e.g., duplicate email or constraint issues)
        return response()->json([
            'message' => 'Database error occurred',
            'error' => $e->getMessage()
        ], 500);
    } catch (\Exception $e) {
        // Handle any other errors
        return response()->json([
            'message' => 'An unexpected error occurred',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    // Attempt to retrieve the user
    $user = User::where('email', $credentials['email'])->first();

    if (!$user || !Hash::check($credentials['password'], $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['Invalid credentials provided.'],
        ]);
    }

    if (!$user->is_active) {
        return response()->json([
            'message' => 'Account is not active. Please contact support or wait for approval.'
        ], 403);
    }

    //$token = $user->createToken('auth_token')->plainTextToken;

     // Create token with the role claim
     $token = $user->createToken('auth_token', ['role:' . $user->role])->plainTextToken;


    return response()->json([
        'user' => $user,
        'token' => $token
    ]);
}


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
