<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class PasswordRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Change password requires authentication
        if ($this->isChangePasswordAction()) {
            return $this->isAuthenticated();
        }

        // Forgot and reset password are public
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if ($this->isChangePasswordAction()) {
            return $this->getChangePasswordRules();
        }

        if ($this->isForgotPasswordAction()) {
            return $this->getForgotPasswordRules();
        }

        if ($this->isResetPasswordAction()) {
            return $this->getResetPasswordRules();
        }

        return [];
    }

    /**
     * Get validation rules for changing password
     */
    private function getChangePasswordRules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Get validation rules for forgot password
     */
    private function getForgotPasswordRules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * Get validation rules for reset password
     */
    private function getResetPasswordRules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Determine if this is a change password action
     */
    private function isChangePasswordAction(): bool
    {
        return $this->route()?->getActionMethod() === 'changePassword' ||
            $this->has('current_password');
    }

    /**
     * Determine if this is a forgot password action
     */
    private function isForgotPasswordAction(): bool
    {
        return $this->route()?->getActionMethod() === 'forgotPassword' ||
            (!$this->has('token') && !$this->has('current_password') && $this->has('email'));
    }

    /**
     * Determine if this is a reset password action
     */
    private function isResetPasswordAction(): bool
    {
        return $this->route()?->getActionMethod() === 'resetPassword' ||
            ($this->has('token') && $this->has('email'));
    }
    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            // Change password messages
            'current_password.required' => 'Please enter your current password.',
            'new_password.required' => 'Please enter a new password.',
            'new_password.confirmed' => 'New password confirmation does not match.',

            // Forgot password messages
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',

            // Reset password messages
            'token.required' => 'Reset token is required.',
            'password.required' => 'Please enter a new password.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
