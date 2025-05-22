<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRequest;
use App\Models\Admin;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class PasswordController extends Controller
{
    /**
     * Change password for authenticated user
     */
    public function changePassword(PasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            // Verify current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                return $this->errorResponse('Current password is incorrect', 422);
            }

            // Update password
            $user->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            // Revoke all tokens except current one
            $currentToken = $user->currentAccessToken();
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();

            return $this->successResponse('Password changed successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Send password reset link using Laravel's built-in functionality
     */
    public function forgotPassword(PasswordRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $email = $validated['email'];

            // Try different user types and send reset link
            $status = $this->sendResetLinkForUserType($email);

            if ($status === Password::RESET_LINK_SENT) {
                return $this->successResponse('Password reset link sent to your email address');
            }

            // Return success even if user not found for security
            return $this->successResponse('If an account with that email exists, a password reset link has been sent');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Reset password using Laravel's built-in functionality
     */
    public function resetPassword(PasswordRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Try reset for different user types
            $status = $this->resetPasswordForUserType($validated);

            if ($status === Password::PASSWORD_RESET) {
                return $this->successResponse('Password reset successfully');
            }

            return $this->errorResponse($this->getResetErrorMessage($status), 422);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Send reset link for the appropriate user type
     */
    private function sendResetLinkForUserType(string $email): string
    {
        // Try User
        if (User::where('email', $email)->exists()) {
            return Password::broker('users')->sendResetLink(['email' => $email]);
        }

        // Try Admin
        if (Admin::where('email', $email)->exists()) {
            return Password::broker('admins')->sendResetLink(['email' => $email]);
        }

        // Try Resident
        if (Resident::where('email', $email)->exists()) {
            return Password::broker('residents')->sendResetLink(['email' => $email]);
        }

        return Password::INVALID_USER;
    }

    /**
     * Reset password for the appropriate user type
     */
    private function resetPasswordForUserType(array $credentials): string
    {
        $email = $credentials['email'];

        // Try User
        if (User::where('email', $email)->exists()) {
            return Password::broker('users')->reset($credentials, function ($user, $password) {
                $this->updatePassword($user, $password);
            });
        }

        // Try Admin
        if (Admin::where('email', $email)->exists()) {
            return Password::broker('admins')->reset($credentials, function ($user, $password) {
                $this->updatePassword($user, $password);
            });
        }

        // Try Resident
        if (Resident::where('email', $email)->exists()) {
            return Password::broker('residents')->reset($credentials, function ($user, $password) {
                $this->updatePassword($user, $password);
            });
        }

        return Password::INVALID_USER;
    }

    /**
     * Update user password and revoke tokens
     */
    private function updatePassword($user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();

        // Revoke all tokens
        $user->tokens()->delete();

        event(new PasswordReset($user));
    }

    /**
     * Get human readable error message for password reset status
     */
    private function getResetErrorMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => 'Invalid or expired reset token',
            Password::INVALID_USER => 'No user found with that email address',
            Password::RESET_THROTTLED => 'Too many reset attempts. Please wait before retrying',
            default => 'Unable to reset password. Please try again'
        };
    }
}
