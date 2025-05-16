<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For regular admin endpoints, just require any authenticated admin
        if ($this->is('admin/profile')) {
            return $this->isAdmin();
        }

        // For admin management endpoints, require super_admin role
        return $this->user() && $this->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $parentRules = parent::rules(); // Gets common filter rules if isFilterAction() is true

        if ($this->isFilterAction()) {
            return array_merge($parentRules, $this->getSpecificFilterRules());
        }

        // For profile viewing, no additional validation needed
        if ($this->is('admin/profile')) {
            return [];
        }

        // For store/create method, use the admin creation rules
        if ($this->isMethod('POST') && !$this->is('admin/admins/filter')) {
            return $this->getStoreRules();
        }

        // For update methods, use the admin update rules
        if ($this->isUpdateRequest()) {
            return $this->getUpdateRules();
        }

        // Default fallback rules (empty if not covered above)
        return [];
    }

    /**
     * Get rules for creating a new admin.
     */
    private function getStoreRules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255', 'unique:admins'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:admin,super_admin'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string'],
            'age' => ['required', 'integer', 'min:18'],
            'gender' => ['required', 'in:male,female'],
            'salary' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    /**
     * Get rules for updating an existing admin.
     */
    private function getUpdateRules(): array
    {
        $adminId = $this->route('admin') ? $this->route('admin')->id : $this->route('id');

        return [
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('admins')->ignore($adminId)],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('admins')->ignore($adminId)],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'role' => ['sometimes', 'in:admin,super_admin'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'string'],
            'age' => ['sometimes', 'integer', 'min:18'],
            'gender' => ['sometimes', 'in:male,female'],
            'salary' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    /**
     * Get the specific filter rules for admins.
     */
    private function getSpecificFilterRules(): array
    {
        return [
            'filters.role' => ['sometimes', 'nullable', 'in:admin,super_admin'],
            'filters.gender' => ['sometimes', 'nullable', 'in:male,female'],
            'filters.age' => ['sometimes', 'array'],
            'filters.age.min' => ['sometimes', 'nullable', 'integer', 'min:18'],
            'filters.age.max' => ['sometimes', 'nullable', 'integer', 'min:18', 'gt:filters.age.min'],
            'filters.salary' => ['sometimes', 'array'],
            'filters.salary.min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'filters.salary.max' => ['sometimes', 'nullable', 'numeric', 'min:0', 'gt:filters.salary.min'],
        ];
    }

    /**
     * Determine if the current request path matches a given pattern.
     */
    public function is(...$patterns): bool
    {
        return $this->getRequest()->is(...$patterns);
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        $parentMessages = parent::messages();
        $specificMessages = [
            'username.unique' => 'This username is already taken.',
            'email.unique' => 'This email address is already registered.',
            'role.in' => 'The role must be either admin or super_admin.',
            'gender.in' => 'The gender must be either male or female.',
            'age.min' => 'The admin must be at least 18 years old.',
            'salary.min' => 'The salary must be at least 0.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}
