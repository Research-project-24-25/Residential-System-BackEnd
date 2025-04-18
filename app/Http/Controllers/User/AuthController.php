<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user and return token.
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

            Auth::login($user);

            $token = $user->createToken($user->email)->plainTextToken;

            return $this->createdResponse('User registered successfully', [
                'user' => $user,
                'token' => $token,
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed: ' . $e->errors(), 422);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Log in the user and return token.
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($credentials)) {
                return $this->errorResponse('Invalid login credentials', 401);
            }

            $user = User::where('email', $credentials['email'])->firstOrFail();
            $token = $user->createToken($user->email)->plainTextToken;

            return $this->successResponse('User logged in successfully', [
                'user' => $user,
                'token' => $token,
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed: ' . $e->errors(), 422);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Log out the current user.
     */
    public function logout(Request $request)
    {
        try {
            $token = $request->user()->currentAccessToken();

            if ($token) {
                $token->delete();
                return $this->successResponse('Logged out successfully');
            }

            return $this->errorResponse('No active token found', 400);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Optional: Get the currently authenticated user.
     */
    public function me(Request $request)
    {
        return $this->successResponse('User data fetched successfully', [
            'user' => $request->user(),
        ]);
    }
}
