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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'property_type' => 'sometimes|in:apartment,house',
            'min_price' => 'sometimes|numeric|min:0',
            'max_price' => 'sometimes|numeric|min:0',
            'bedrooms' => 'sometimes|integer|min:0',
            'min_bedrooms' => 'sometimes|integer|min:0',
            'max_bedrooms' => 'sometimes|integer|min:0',
            'bathrooms' => 'sometimes|integer|min:0',
            'min_bathrooms' => 'sometimes|integer|min:0',
            'max_bathrooms' => 'sometimes|integer|min:0',
            'min_area' => 'sometimes|integer|min:0',
            'max_area' => 'sometimes|integer|min:0',
            'status' => 'sometimes|string',
            'price_type' => 'sometimes|string',
            'currency' => 'sometimes|string|size:3',
            'features' => 'sometimes|string',
            'building_id' => 'sometimes|integer|exists:buildings,id',
            'floor_id' => 'sometimes|integer|exists:floors,id',
            'floor_number' => 'sometimes|string',
            'min_lot_size' => 'sometimes|integer|min:0',
            'max_lot_size' => 'sometimes|integer|min:0',
            'property_style' => 'sometimes|string',
            'sort' => 'sometimes|string',
            'direction' => 'sometimes|in:asc,desc',
            'search' => 'sometimes|string|max:255',
            'created_from' => 'sometimes|date',
            'created_to' => 'sometimes|date|after_or_equal:created_from',
            'updated_from' => 'sometimes|date',
            'updated_to' => 'sometimes|date|after_or_equal:updated_from',
        ];
    }
}
