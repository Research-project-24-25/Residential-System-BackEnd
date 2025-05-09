<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PropertyRequest extends BaseFormRequest
{
    // authorize() method can be removed as parent::authorize() defaults to true.

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $parentRules = parent::rules(); // Gets common filter rules if isFilterAction() is true

        if ($this->isFilterAction()) {
            return array_merge($parentRules, $this->getSpecificFilterRules());
        }

        // Entity specific rules for create/update
        return array_merge($parentRules, $this->getPropertyRules()); // parentRules will be empty if not filter action
    }

    /**
     * Get specific rules for filtering properties.
     * Common filter rules are handled by BaseFormRequest.
     */
    private function getSpecificFilterRules(): array
    {
        return [
            // Support for single value or array of values
            'filters.type' => ['sometimes', 'nullable'],
            'filters.type.*' => ['string', Rule::in(['apartment', 'house', 'villa', 'studio'])],

            'filters.status' => ['sometimes', 'nullable'],
            'filters.status.*' => ['string', Rule::in(['available_now', 'under_construction', 'sold', 'rented'])],

            'filters.currency' => ['sometimes', 'nullable'],
            'filters.currency.*' => ['string'],

            // Range filters
            'filters.price' => ['sometimes', 'array'],
            'filters.price.min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'filters.price.max' => ['sometimes', 'nullable', 'numeric', 'min:0', 'gt:filters.price.min'],

            'filters.occupancy_limit' => ['sometimes', 'array'],
            'filters.occupancy_limit.min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'filters.occupancy_limit.max' => ['sometimes', 'nullable', 'integer', 'min:0', 'gte:filters.occupancy_limit.min'],

            'filters.bedrooms' => ['sometimes', 'array'],
            'filters.bedrooms.min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'filters.bedrooms.max' => ['sometimes', 'nullable', 'integer', 'min:0', 'gte:filters.bedrooms.min'],

            'filters.bathrooms' => ['sometimes', 'array'],
            'filters.bathrooms.min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'filters.bathrooms.max' => ['sometimes', 'nullable', 'integer', 'min:0', 'gte:filters.bathrooms.min'],

            'filters.area' => ['sometimes', 'array'],
            'filters.area.min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'filters.area.max' => ['sometimes', 'nullable', 'integer', 'min:0', 'gte:filters.area.min'],

            'filters.features' => ['sometimes', 'array'],
            'filters.features.*' => ['sometimes', 'string'],
        ];
    }

    /**
     * Get rules for creating and updating properties
     */
    private function getPropertyRules(): array
    {
        $isUpdate = $this->isUpdateRequest();
        $requiredRule = $isUpdate ? 'sometimes' : 'required';

        $rules = [
            'label' => [$requiredRule, 'string', 'max:255'],
            'type' => [$requiredRule, 'string', Rule::in(['apartment', 'house', 'villa', 'studio'])],
            'price' => [$requiredRule, 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'status' => ['sometimes', 'string', Rule::in(['available_now', 'under_construction', 'sold', 'rented'])],
            'description' => ['sometimes', 'nullable', 'string'],
            'occupancy_limit' => ['sometimes', 'integer', 'min:0'],
            'bedrooms' => ['sometimes', 'integer', 'min:0'],
            'bathrooms' => ['sometimes', 'integer', 'min:0'],
            'area' => ['sometimes', 'integer', 'min:0'],
            'features' => ['sometimes', 'nullable', 'array'],
            'features.*' => ['sometimes', 'string'],
            'images' => ['sometimes', 'nullable'],
            // Image upload validation for 'images[]' (array of files)
            // This rule applies to both create and update, 'sometimes' handles if it's present
            'images.*' => ['sometimes', 'file', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
        
        // Simpler way to handle images, as 'images.*' with 'sometimes' covers both create and update.
        // The 'images' field itself being 'nullable' handles removal if an empty array or null is sent.
        // if ($isUpdate) {
        //     $rules['images.*'] = 'sometimes|file|image|mimes:jpeg,png,jpg,gif|max:2048';
        // } else {
        //     $rules['images.*'] = 'sometimes|file|image|mimes:jpeg,png,jpg,gif|max:2048';
        // }

        // Add unique rule for label on create or when changing label on update
        if (!$isUpdate) {
            $rules['label'][] = Rule::unique('properties', 'label');
        } else {
            // Ensure $this->route('id') is available and correct for the update route
            // It might be $this->property->id or $this->route('property') depending on route binding
            $propertyId = $this->route('property') ? $this->route('property')->id : $this->route('id');
            if ($propertyId) {
                 $rules['label'][] = Rule::unique('properties', 'label')->ignore($propertyId);
            } else {
                // Fallback if ID cannot be determined, or handle error
                // This might happen if the route parameter name is different
                // Or if implicit route model binding is not used and 'id' is not explicitly passed.
                // For now, we'll assume it's available or the unique rule might fail on update if label is unchanged.
                // A more robust solution might involve checking $this->property if route model binding is used.
                 $rules['label'][] = Rule::unique('properties', 'label')->ignore($this->input('label'), 'label');
            }
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $parentMessages = parent::messages(); // Get common filter messages
        $specificMessages = [
            // Add any specific messages for this request if needed
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}
