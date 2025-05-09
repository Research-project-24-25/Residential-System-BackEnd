<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $isAdmin = $this->user()->getTable() === 'admins';
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        // Get the current route name or action to determine the context
        $action = $this->route() ? $this->route()->getActionMethod() : null;

        // Rules for filtering services
        if ($action === 'filter') {
            return $this->getFilterRules();
        }

        // Base rules that apply to both create and update
        $rules = [
            'name' => ['required', 'string', 'max:255'],
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

        // For updates, make fields optional
        if ($isUpdate) {
            $rules['name'] = ['sometimes', 'string', 'max:255'];
            $rules['type'] = ['sometimes', 'string', Rule::in(['utility', 'security', 'cleaning', 'other'])];
            $rules['base_price'] = ['sometimes', 'numeric', 'min:0'];
        }

        return $rules;
    }

    /**
     * Get rules for filtering services
     */
    private function getFilterRules(): array
    {
        return [
            'filters' => 'sometimes|array',

            // Support for single value or array of values
            'filters.type' => 'sometimes',
            'filters.type.*' => 'string|in:utility,security,cleaning,other',

            // Boolean filters
            'filters.is_recurring' => 'sometimes|boolean',
            'filters.is_active' => 'sometimes|boolean',

            // Range filters
            'filters.base_price' => 'sometimes|array',
            'filters.base_price.min' => 'sometimes|numeric|min:0',
            'filters.base_price.max' => 'sometimes|numeric|gt:filters.base_price.min',

            // Date filters
            'filters.created_at' => 'sometimes|array',
            'filters.created_at.from' => 'sometimes|date',
            'filters.created_at.to' => 'sometimes|date|after_or_equal:filters.created_at.from',

            'filters.updated_at' => 'sometimes|array',
            'filters.updated_at.from' => 'sometimes|date',
            'filters.updated_at.to' => 'sometimes|date|after_or_equal:filters.updated_at.from',

            // Search
            'filters.search' => 'sometimes|string|max:255',

            // Sorting
            'sort' => 'sometimes|array',
            'sort.field' => 'sometimes|string',
            'sort.direction' => 'sometimes|string|in:asc,desc',

            // Pagination
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
