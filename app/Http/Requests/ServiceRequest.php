<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ServiceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $parentRules = parent::rules(); // Gets common filter rules if isFilterAction() is true

        if ($this->isFilterAction()) {
            return array_merge($parentRules, $this->getSpecificFilterRules());
        }

        // Entity specific rules for create/update
        $specificRules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('services', 'name')->ignore($this->route('service'))],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'string', Rule::in(['utility', 'security', 'cleaning', 'other'])],
            'base_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'unit_of_measure' => ['nullable', 'string', 'max:50'],
            'is_recurring' => ['sometimes', 'boolean'],
            'recurrence' => ['nullable', 'required_if:is_recurring,true', Rule::in(['monthly', 'quarterly', 'yearly'])],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];

        if ($this->isUpdateRequest()) {
            $specificRules['name'] = ['sometimes', 'string', 'max:255', Rule::unique('services', 'name')->ignore($this->route('service'))];
            $specificRules['type'] = ['sometimes', 'string', Rule::in(['utility', 'security', 'cleaning', 'other'])];
            $specificRules['base_price'] = ['sometimes', 'numeric', 'min:0'];
        } else {
            // For create, ensure name is unique without ignore
            $specificRules['name'] = ['required', 'string', 'max:255', Rule::unique('services', 'name')];
        }


        return array_merge($parentRules, $specificRules); // parentRules will be empty if not filter action
    }

    private function getSpecificFilterRules(): array
    {
        return [
            // Support for single value or array of values
            'filters.type' => ['sometimes', 'nullable'],
            'filters.type.*' => ['string', Rule::in(['utility', 'security', 'cleaning', 'other'])],

            // Boolean filters
            'filters.is_recurring' => ['sometimes', 'boolean'],
            'filters.is_active' => ['sometimes', 'boolean'],

            // Range filters
            'filters.base_price' => ['sometimes', 'array'],
            'filters.base_price.min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'filters.base_price.max' => ['sometimes', 'nullable', 'numeric', 'min:0', 'gt:filters.base_price.min'],
        ];
    }

    public function messages(): array
    {
        $parentMessages = parent::messages();
        $specificMessages = [
            'name.unique' => 'A service with this name already exists.',
            'recurrence.required_if' => 'The recurrence field is required when the service is recurring.',
            // Add other specific messages if needed
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}
