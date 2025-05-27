<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PropertyResidentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->isAdmin();
    }

    public function rules(): array
    {
        $isUpdate = $this->isUpdateRequest();
        $requiredRule = $isUpdate ? 'sometimes' : 'required';

        return [
            'property_id' => [$requiredRule, 'exists:properties,id'],
            'resident_id' => [$requiredRule, 'exists:residents,id'],
            'relationship_type' => [$requiredRule, Rule::in(['buyer', 'co_buyer', 'renter'])],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'ownership_share' => ['nullable', 'numeric', 'between:0,100'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.exists' => 'The selected property does not exist.',
            'resident_id.exists' => 'The selected resident does not exist.',
            'relationship_type.in' => 'The relationship type must be buyer, co_buyer, or renter.',
            'ownership_share.between' => 'The ownership share must be between 0 and 100 percent.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate business rules based on relationship type
            $relationshipType = $this->input('relationship_type');

            if (in_array($relationshipType, ['buyer', 'co_buyer'])) {
                // Buyers should have sale_price
                if (!$this->filled('sale_price')) {
                    $validator->errors()->add('sale_price', 'Sale price is required for buyers.');
                }

                // Co-buyers should have ownership_share
                if ($relationshipType === 'co_buyer' && !$this->filled('ownership_share')) {
                    $validator->errors()->add('ownership_share', 'Ownership share is required for co-buyers.');
                }

                // Clear rental fields for buyers
                if ($this->filled('monthly_rent')) {
                    $validator->errors()->add('monthly_rent', 'Monthly rent should not be set for buyers.');
                }
            }

            if ($relationshipType === 'renter') {
                // Renters should have monthly_rent
                if (!$this->filled('monthly_rent')) {
                    $validator->errors()->add('monthly_rent', 'Monthly rent is required for renters.');
                }

                // Clear sale fields for renters
                if ($this->filled('sale_price')) {
                    $validator->errors()->add('sale_price', 'Sale price should not be set for renters.');
                }
                if ($this->filled('ownership_share')) {
                    $validator->errors()->add('ownership_share', 'Ownership share should not be set for renters.');
                }
            }
        });
    }
}
