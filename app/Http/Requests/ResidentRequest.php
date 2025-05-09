<?php

namespace App\Http\Requests;

use App\Models\Admin;
use Illuminate\Validation\Rule;
// FormRequest is extended by BaseFormRequest

class ResidentRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $parentRules = parent::rules(); // Gets common filter rules if isFilterAction() is true

        if ($this->isFilterAction()) {
            return array_merge($parentRules, $this->getSpecificFilterRules());
        }

        // Entity specific rules for create/update
        return array_merge($parentRules, $this->getEntityRules()); // parentRules will be empty if not filter action
    }

    /**
     * Get specific filter rules for residents.
     * Common filter rules are handled by BaseFormRequest.
     */
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
                'password' => ['sometimes', 'string', 'min:8', 'confirmed'], // Added confirmed for password updates
                'phone_number' => ['sometimes', 'string'],
                'age' => ['sometimes', 'integer', 'min:0'],
                'gender' => ['sometimes', Rule::in(['male', 'female'])],
                'profile_image' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'], // Allow updating image

                'property_id' => ['sometimes', 'integer', 'exists:properties,id'],
                'relationship_type' => ['sometimes', Rule::in(['buyer', 'co_buyer', 'renter'])],
                'sale_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'ownership_share' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
                'monthly_rent' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'start_date' => ['sometimes', 'nullable', 'date'],
                'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
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

                'property_id' => ['required', 'integer', 'exists:properties,id'],
                'relationship_type' => ['required', Rule::in(['buyer', 'co_buyer', 'renter'])],
                'sale_price' => ['nullable', 'numeric', 'min:0'],
                'ownership_share' => ['nullable', 'numeric', 'between:0,100'],
                'monthly_rent' => ['nullable', 'numeric', 'min:0'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            ];
        }
        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
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
