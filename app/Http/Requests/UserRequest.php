<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->isAdmin();
    }

    public function rules(): array
    {
        $parentRules = parent::rules();

        if ($this->isFilterAction()) {
            return array_merge($parentRules, $this->getSpecificFilterRules());
        }

        return array_merge($parentRules, $this->getEntityRules());
    }

    private function getSpecificFilterRules(): array
    {
        return [
            'filters.email_verified_at' => ['sometimes', 'array'],
            'filters.email_verified_at.from' => ['sometimes', 'nullable', 'date'],
            'filters.email_verified_at.to' => ['sometimes', 'nullable', 'date', 'after_or_equal:filters.email_verified_at.from'],
        ];
    }

    private function getEntityRules(): array
    {
        $isUpdate = $this->isUpdateRequest();
        $userId = null;

        if ($isUpdate) {
            $userId = $this->route('user') ? $this->route('user')->id : $this->route('id');
        }

        $rules = [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'email' => [
                $isUpdate ? 'sometimes' : 'required',
                'email',
                'max:255',
                $isUpdate && $userId
                    ? Rule::unique('users')->ignore($userId)
                    : Rule::unique('users')
            ],
            'password' => [$isUpdate ? 'sometimes' : 'required', 'confirmed', Password::defaults()],
        ];

        return $rules;
    }

    public function messages(): array
    {
        $parentMessages = parent::messages();
        $specificMessages = [
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}
