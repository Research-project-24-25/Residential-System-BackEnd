<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResidentResource;
use App\Models\Admin;
use App\Models\Resident;
use App\Models\User;
use App\Http\Resources\AdminResource;
use App\Http\Resources\UserResource;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            // Try authenticating as Admin
            $admin = Admin::where('email', $request->email)->first();
            if ($admin && Hash::check($request->password, $admin->password)) {
                $token = $admin->createToken('admin-token')->plainTextToken;

                return $this->successResponse('Logged in successfully as admin', [
                    'user' => new AdminResource($admin),
                    'token' => $token,
                    'user_type' => 'admin',
                ]);
            }

            // Try authenticating as Resident
            $resident = Resident::where('email', $request->email)->first();
            if ($resident && Hash::check($request->password, $resident->password)) {
                $token = $resident->createToken('resident-token')->plainTextToken;

                return $this->successResponse('Logged in successfully as resident', [
                    'user' => new ResidentResource($resident),
                    'token' => $token,
                    'user_type' => 'resident'
                ]);
            }

            // Try authenticating as regular User
            $user = User::where('email', $request->email)->first();
            if ($user && Hash::check($request->password, $user->password)) {
                // Check if email is verified for regular users
                if (!$user->hasVerifiedEmail()) {
                    return $this->errorResponse('Please verify your email address first.', 403);
                }

                $token = $user->createToken($user->email)->plainTextToken;

                return $this->successResponse('Logged in successfully', [
                    'user' => new UserResource($user),
                    'token' => $token,
                    'user_type' => 'user'
                ]);
            }

            // No matching user found
            return $this->errorResponse('Invalid credentials', 401);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Send verification email
            event(new Registered($user));

            // Generate Sanctum token
            $token = $user->createToken($user->email)->plainTextToken;

            return $this->createdResponse('User registered successfully. Please verify your email address.', [
                'user' => new UserResource($user),
                'token' => $token,
                'user_type' => 'user',
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $userType = match ($user->getTable()) {
                'admins' => 'admin',
                'residents' => 'resident',
                default => 'user'
            };

            $resource = match ($userType) {
                'admin' => new AdminResource($user),
                'resident' => new ResidentResource($user),
                default => new UserResource($user)
            };

            return $this->successResponse('User data retrieved successfully', [
                'user' => $resource,
                'user_type' => $userType,
            ]);
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
}
