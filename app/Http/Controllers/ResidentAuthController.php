<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Traits\ApiResponse;
use App\Traits\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class ResidentAuthController extends Controller
{
    use ApiResponse, ExceptionHandler;

    /**
     * Handle resident login
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $resident = Resident::where('email', $request->email)->first();

            if (!$resident || !Hash::check($request->password, $resident->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $token = $resident->createToken('resident-token')->plainTextToken;

            return $this->successResponse('Resident logged in successfully', [
                'resident' => $resident->only(['id', 'username', 'email', 'first_name', 'last_name']),
                'token' => $token,
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get authenticated resident profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $resident = $request->user();

            return $this->successResponse(
                'Resident profile retrieved successfully',
                $resident->load('properties')
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Logout resident
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse('Resident logged out successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
