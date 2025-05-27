<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ResidentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->isAdmin();
    }

    public function rules(): array
    {
        $parentRules = parent::rules(); // Gets common filter rules if isFilterAction() is true

        if ($this->isFilterAction()) {
            return array_merge($parentRules, $this->getSpecificFilterRules());
        }

        // Entity specific rules for create/update
        return array_merge($parentRules, $this->getEntityRules()); // parentRules will be empty if not filter action
    }

    private function getSpecificFilterRules(): array
    {
        return [
            // Basic filters
            'filters.gender' => ['sometimes', 'nullable'],
            'filters.gender.*' => ['string', Rule::in(['male', 'female'])],

            // Range filters
            'filters.age' => ['sometimes', 'array'],
            'filters.age.min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'filters.age.max' => ['sometimes', 'nullable', 'integer', 'min:0', 'gt:filters.age.min'],

            // Property relationship filters
            'filters.property_id' => ['sometimes', 'nullable', 'exists:properties,id'],
            'filters.relationship_type' => ['sometimes', 'nullable'],
            'filters.relationship_type.*' => ['string', Rule::in(['buyer', 'co_buyer', 'renter'])],
        ];
    }

    /**
     * Get resident creation/update rules
     */
    // Update the getEntityRules method in ResidentRequest to remove property relationship fields

    private function getEntityRules(): array
    {
        $isUpdate = $this->isUpdateRequest();
        $rules = [];

        if ($isUpdate) {
            $residentId = $this->route('resident') ? $this->route('resident')->id : $this->route('id');

            $rules = [
                'username' => ['sometimes', 'string', 'max:255'],
                'first_name' => ['sometimes', 'string', 'max:255'],
                'last_name' => ['sometimes', 'string', 'max:255'],
                'email' => [
                    'sometimes',
                    'email',
                    Rule::unique('residents')->ignore($residentId)
                ],
                'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
                'phone_number' => ['sometimes', 'string'],
                'age' => ['sometimes', 'integer', 'min:0'],
                'gender' => ['sometimes', Rule::in(['male', 'female'])],
                'profile_image' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            ];
        } else { // Create rules
            $rules = [
                'username' => ['required', 'string', 'max:255', 'unique:residents,username'],
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:residents,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'phone_number' => ['required', 'string'],
                'age' => ['required', 'integer', 'min:0'],
                'gender' => ['required', Rule::in(['male', 'female'])],
                'profile_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            ];
        }
        return $rules;
    }

    public function messages(): array
    {
        $parentMessages = parent::messages();
        $specificMessages = [
            'email.unique' => 'This email address is already registered.',
            'username.unique' => 'This username is already taken.',
            // Add other specific messages if needed
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}
