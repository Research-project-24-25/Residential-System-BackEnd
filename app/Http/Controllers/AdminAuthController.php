<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\JsonResponse;
use Throwable;

class AdminAuthController extends BaseController
{
    public function register(Request $request): JsonResponse
    {
        try {
            if (!$request->user()->isSuperAdmin()) {
                return $this->forbiddenResponse('Only super admins can create admin accounts.');
            }

            $validated = $request->validate([
                'username' => ['required', 'string', 'max:255', 'unique:admins'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:admins'],
                'password' => ['required', 'confirmed', Password::defaults()],
                'role' => ['required', 'in:admin,super_admin'],
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'phone_number' => ['required', 'string'],
                'age' => ['required', 'integer', 'min:18'],
                'gender' => ['required', 'in:male,female'],
            ]);
            $validated['password'] = Hash::make($validated['password']);

            $admin = Admin::create($validated);
            $token = $admin->createToken('admin-token')->plainTextToken;

            return $this->createdResponse(
                'Admin registered successfully',
                [
                    'admin' => new AdminResource($admin),
                    'token' => $token
                ]
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);

            $admin = Admin::where('email', $validated['email'])->first();

            if (!$admin || !Hash::check($validated['password'], $admin->password)) {
                return $this->unauthorizedResponse('Invalid credentials');
            }

            $token = $admin->createToken('admin-token')->plainTextToken;

            return $this->successResponse(
                'Logged in successfully',
                [
                    'admin' => new AdminResource($admin),
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
                new AdminResource($request->user())
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
