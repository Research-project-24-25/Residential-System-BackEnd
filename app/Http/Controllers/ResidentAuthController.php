<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResidentResource;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Throwable;

class ResidentAuthController extends BaseController
{
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            $resident = Resident::where('email', $validated['email'])->first();

            if (!$resident || !Hash::check($validated['password'], $resident->password)) {
                return $this->unauthorizedResponse('Invalid credentials');
            }

            $token = $resident->createToken('resident-token')->plainTextToken;

            return $this->successResponse(
                'Logged in successfully',
                [
                    'resident' => new ResidentResource($resident->load(['house', 'apartment.floor.building'])),
                    'token' => $token
                ]
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->successResponse('Logged out successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function profile(Request $request): JsonResponse
    {
        try {
            return $this->successResponse(
                'Profile retrieved successfully',
                new ResidentResource($request->user()->load(['house', 'apartment.floor.building']))
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
