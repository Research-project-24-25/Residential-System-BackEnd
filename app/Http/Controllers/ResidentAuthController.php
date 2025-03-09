<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResidentAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $resident = Resident::where('email', $validated['email'])->first();

        if (! $resident || ! Hash::check($validated['password'], $resident->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $resident->createToken('resident-token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'resident' => $resident->load(['house', 'apartment.floor.building']),
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user()->load(['house', 'apartment.floor.building']));
    }
}
