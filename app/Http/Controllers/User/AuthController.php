<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use Throwable;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user and send verification email.
     */
    public function register(Request $request)
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

            // Dispatch verification email
            event(new Registered($user));

            return $this->createdResponse('User registered successfully. Please verify your email address.', [
                'user' => $user,
            ]);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Get the currently authenticated user.
     */
    public function me(Request $request)
    {
        return $this->successResponse('User data fetched successfully', [
            'user' => $request->user(),
        ]);
    }
}
