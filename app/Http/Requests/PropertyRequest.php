<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'property_type' => 'sometimes|string|in:apartment,house',
            'filters' => 'sometimes|array',

            // Common filters
            'filters.price' => 'sometimes|array',
            'filters.price.min' => 'sometimes|numeric|min:0',
            'filters.price.max' => 'sometimes|numeric|gt:filters.price.min',

            'filters.bedrooms' => 'sometimes|array',
            'filters.bedrooms.min' => 'sometimes|integer|min:0',
            'filters.bedrooms.max' => 'sometimes|integer|gte:filters.bedrooms.min',

            'filters.bathrooms' => 'sometimes|array',
            'filters.bathrooms.min' => 'sometimes|integer|min:0',
            'filters.bathrooms.max' => 'sometimes|integer|gte:filters.bathrooms.min',

            'filters.area' => 'sometimes|array',
            'filters.area.min' => 'sometimes|integer|min:0',
            'filters.area.max' => 'sometimes|integer|gte:filters.area.min',

            'filters.status' => 'sometimes|array',
            'filters.price_type' => 'sometimes|string',
            'filters.currency' => 'sometimes|string',
            'filters.features' => 'sometimes|array',

            // Date filters
            'filters.created_at' => 'sometimes|array',
            'filters.created_at.from' => 'sometimes|date',
            'filters.created_at.to' => 'sometimes|date|after_or_equal:filters.created_at.from',

            'filters.updated_at' => 'sometimes|array',
            'filters.updated_at.from' => 'sometimes|date',
            'filters.updated_at.to' => 'sometimes|date|after_or_equal:filters.updated_at.from',

            // Apartment-specific filters
            'filters.building_id' => 'sometimes|integer|exists:buildings,id',
            'filters.floor_id' => 'sometimes|integer|exists:floors,id',
            'filters.floor_number' => 'sometimes|string',

            // House-specific filters
            'filters.lot_size' => 'sometimes|array',
            'filters.lot_size.min' => 'sometimes|integer|min:0',
            'filters.lot_size.max' => 'sometimes|integer|gte:filters.lot_size.min',

            'filters.property_style' => 'sometimes|array',

            // Search
            'filters.search' => 'sometimes|string|max:255',

            // Sorting
            'sort' => 'sometimes|array',
            'sort.field' => 'sometimes|string',
            'sort.direction' => 'sometimes|string|in:asc,desc',
        ];
    }
}
