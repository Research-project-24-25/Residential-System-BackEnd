<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResidentResource;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class ResidentAuthController extends Controller
{
    public function login(Request $request): JsonResponse
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
            'resident' => new ResidentResource($resident->load(['house', 'apartment.floor.building'])),
            'token' => $token
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json(
            new ResidentResource($request->user()->load(['house', 'apartment.floor.building']))
        );
    }
}
