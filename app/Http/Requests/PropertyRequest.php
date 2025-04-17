<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyRequest extends FormRequest
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
        // Get the current route name or action to determine the context
        $action = $this->route() ? $this->route()->getActionMethod() : null;

        // Rules for filtering properties
        if ($action === 'index' || $this->has('filters')) {
            return $this->getFilterRules();
        }

        // Rules for storing or updating properties
        return $this->getPropertyRules();
    }

    /**
     * Get rules for filtering properties
     */
    private function getFilterRules(): array
    {
        return [
            'filters' => 'sometimes|array',
            'filters.type' => 'sometimes|string|in:apartment,house,villa',
            'filters.status' => 'sometimes|string|in:available,sold,rented,pending',
            'filters.price_type' => 'sometimes|string|in:sale,rent',
            'filters.currency' => 'sometimes|string',

            // Range filters
            'filters.price' => 'sometimes|array',
            'filters.price.min' => 'sometimes|numeric|min:0',
            'filters.price.max' => 'sometimes|numeric|gt:filters.price.min',

            'filters.occupancy_limit' => 'sometimes|array',
            'filters.occupancy_limit.min' => 'sometimes|integer|min:0',
            'filters.occupancy_limit.max' => 'sometimes|integer|gte:filters.occupancy_limit.min',

            'filters.bedrooms' => 'sometimes|array',
            'filters.bedrooms.min' => 'sometimes|integer|min:0',
            'filters.bedrooms.max' => 'sometimes|integer|gte:filters.bedrooms.min',

            'filters.bathrooms' => 'sometimes|array',
            'filters.bathrooms.min' => 'sometimes|integer|min:0',
            'filters.bathrooms.max' => 'sometimes|integer|gte:filters.bathrooms.min',

            'filters.area' => 'sometimes|array',
            'filters.area.min' => 'sometimes|integer|min:0',
            'filters.area.max' => 'sometimes|integer|gte:filters.area.min',

            'filters.features' => 'sometimes|array',

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
        ];
    }

    /**
     * Get rules for creating and updating properties
     */
    private function getPropertyRules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $required = $isUpdate ? 'sometimes' : 'required';

        $rules = [
            'identifier' => [$required, 'string', 'max:255'],
            'type' => [$required, 'string', 'in:apartment,house,villa'],
            'price' => [$required, 'numeric', 'min:0'],
            'currency' => 'sometimes|string|max:3',
            'price_type' => 'sometimes|string|in:sale,rent',
            'status' => 'sometimes|string|in:available,sold,rented,pending',
            'description' => 'sometimes|nullable|string',
            'occupancy_limit' => 'sometimes|integer|min:0',
            'bedrooms' => 'sometimes|integer|min:0',
            'bathrooms' => 'sometimes|integer|min:0',
            'area' => 'sometimes|integer|min:0',
            'images' => 'sometimes|nullable|array',
            'images.*' => 'sometimes|string|url',
            'features' => 'sometimes|nullable|array',
            'features.*' => 'sometimes|string',
        ];

        // Add unique rule for identifier on create or when changing identifier on update
        if (!$isUpdate) {
            $rules['identifier'][] = 'unique:properties,identifier';
        } else {
            $rules['identifier'][] = Rule::unique('properties', 'identifier')->ignore($this->route('property'));
        }

        return $rules;
    }
}
