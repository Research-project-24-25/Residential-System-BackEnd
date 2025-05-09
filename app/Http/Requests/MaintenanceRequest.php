<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceRequest extends FormRequest
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

        // Rules for filtering maintenance types
        if ($action === 'filter') {
            return $this->getFilterRules();
        }

        // Base rules that apply to both create and update
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', 'string', Rule::in(['plumbing', 'electrical', 'hvac', 'structural', 'appliances', 'landscaping', 'painting', 'other'])],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'estimated_hours' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        // For updates, make fields optional
        if ($isUpdate) {
            $rules['name'] = ['sometimes', 'string', 'max:255'];
            $rules['category'] = ['sometimes', 'string', Rule::in(['plumbing', 'electrical', 'hvac', 'structural', 'appliances', 'landscaping', 'painting', 'other'])];
        }

        return $rules;
    }

    /**
     * Get rules for filtering maintenance types
     */
    private function getFilterRules(): array
    {
        return [
            'filters' => 'sometimes|array',

            // Support for single value or array of values
            'filters.category' => 'sometimes',
            'filters.category.*' => 'string|in:plumbing,electrical,hvac,structural,appliances,landscaping,painting,other',

            // Boolean filters
            'filters.is_active' => 'sometimes|boolean',

            // Range filters
            'filters.estimated_cost' => 'sometimes|array',
            'filters.estimated_cost.min' => 'sometimes|numeric|min:0',
            'filters.estimated_cost.max' => 'sometimes|numeric|gt:filters.estimated_cost.min',

            'filters.estimated_hours' => 'sometimes|array',
            'filters.estimated_hours.min' => 'sometimes|integer|min:0',
            'filters.estimated_hours.max' => 'sometimes|integer|gt:filters.estimated_hours.min',

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
