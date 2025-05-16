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

        $isUpdate = $this->isUpdateRequest();
        $adminId = $this->route('admin') ? $this->route('admin')->id : $this->route('id');

        // Entity specific rules for create/update
        $rules = [
            'username' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('admins')->ignore($adminId)],
            'email' => [$isUpdate ? 'sometimes' : 'required', 'string', 'email', 'max:255', Rule::unique('admins')->ignore($adminId)],
            'password' => [$isUpdate ? 'sometimes' : 'required', 'confirmed', Password::defaults()],
            'role' => [$isUpdate ? 'sometimes' : 'required', 'in:admin,super_admin'],
            'first_name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'last_name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'phone_number' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'age' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:18'],
            'gender' => [$isUpdate ? 'sometimes' : 'required', 'in:male,female'],
            'salary' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0', 'decimal:0,2'],
        ];

        return array_merge($parentRules, $rules);
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
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}
