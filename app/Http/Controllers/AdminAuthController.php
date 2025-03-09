<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\JsonResponse;

class AdminAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Only super admins can create admin accounts.'], 403);
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

        return response()->json([
            'message' => 'Admin registered successfully',
            'admin' => new AdminResource($admin),
            'token' => $token
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $admin = Admin::where('email', $validated['email'])->first();

        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $admin->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'admin' => new AdminResource($admin),
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
        return response()->json(new AdminResource($request->user()));
    }
}
