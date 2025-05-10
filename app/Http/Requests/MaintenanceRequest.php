<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class MaintenanceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $parentRules = parent::rules(); // Gets common filter rules if isFilterAction() is true

        if ($this->isFilterAction()) {
            return array_merge($parentRules, $this->getSpecificFilterRules());
        }

        // Entity specific rules for create/update
        $specificRules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => [
                'required', 
                'string', 
                function ($attribute, $value, $fail) {
                    $allowedValues = ['plumbing', 'electrical', 'hvac', 'structural', 'appliances', 'landscaping', 'painting', 'other'];
                    if (!in_array(strtolower($value), $allowedValues)) {
                        $fail('The '.$attribute.' must be one of: '.implode(', ', $allowedValues));
                    }
                }
            ],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'estimated_hours' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($this->isUpdateRequest()) {
            $specificRules['name'] = ['sometimes', 'string', 'max:255'];
            $specificRules['category'] = ['sometimes', 'string', Rule::in(['plumbing', 'electrical', 'hvac', 'structural', 'appliances', 'landscaping', 'painting', 'other'])];
        }

        return array_merge($parentRules, $specificRules); // parentRules will be empty if not filter action
    }

    private function getSpecificFilterRules(): array
    {
        return [
            // 'filters' => 'sometimes|array', // This is in common rules
            // Support for single value or array of values
            'filters.category' => ['sometimes', 'nullable'], // Making it nullable if empty string is passed
            'filters.category.*' => ['string', Rule::in(['plumbing', 'electrical', 'hvac', 'structural', 'appliances', 'landscaping', 'painting', 'other'])],

            // Boolean filters
            'filters.is_active' => ['sometimes', 'boolean'],

            // Range filters
            'filters.estimated_cost' => ['sometimes', 'array'],
            'filters.estimated_cost.min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'filters.estimated_cost.max' => ['sometimes', 'nullable', 'numeric', 'min:0', 'gt:filters.estimated_cost.min'], // min:0 added for consistency if min is not present

            'filters.estimated_hours' => ['sometimes', 'array'],
            'filters.estimated_hours.min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'filters.estimated_hours.max' => ['sometimes', 'nullable', 'integer', 'min:0', 'gt:filters.estimated_hours.min'], // min:0 added
        ];
    }

    public function messages(): array
    {
        $parentMessages = parent::messages(); // Get common filter messages
        $specificMessages = [
            // Add any specific messages for this request if needed
            // e.g. 'filters.category.*.in' => 'Invalid category selected for filtering.',
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}
