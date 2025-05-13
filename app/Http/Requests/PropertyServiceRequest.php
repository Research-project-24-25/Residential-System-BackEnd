<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PropertyServiceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->isAdmin();
    }

    public function rules(): array
    {
        $isUpdate = $this->isUpdateRequest();
        
        $rules = [
            'billing_type' => [
                $isUpdate ? 'sometimes' : 'required',
                Rule::in(['fixed', 'area_based', 'prepaid'])
            ],
            'price' => [
                $isUpdate ? 'sometimes' : 'required', 
                'numeric',
                'min:0'
            ],
            'status' => [
                'sometimes',
                Rule::in(['active', 'inactive', 'pending_payment', 'expired']), 
            ],
            'details' => [
                'sometimes',
                'nullable',
                'json'
            ],
            'activated_at' => [
                'sometimes', 
                'nullable',
                'date'
            ],
            'expires_at' => [
                'sometimes',
                'nullable', 
                'date',
                'after_or_equal:activated_at'
            ]
        ];

        // Add rules specific to store/create
        if (!$isUpdate) {
            $rules['property_id'] = [
                'required',
                Rule::exists('properties', 'id'),
                Rule::unique('property_service')->where(function ($query) {
                    return $query->where('service_id', $this->input('service_id')); 
                })
            ];
            $rules['service_id'] = [
                'required',
                Rule::exists('services', 'id')
            ];
        }

        return $rules;
    }

    public function messages(): array 
    {
        return [
            'billing_type.in' => 'The billing type must be one of: fixed, area_based, prepaid.',
            'status.in' => 'The status must be one of: active, inactive, pending_payment, expired.',
            'expires_at.after_or_equal' => 'The expiry date must be after or equal to the activation date.',
            'property_id.unique' => 'This service is already assigned to the specified property.'
        ];
    }
}