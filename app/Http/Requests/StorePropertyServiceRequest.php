<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StorePropertyServiceRequest extends BaseFormRequest
{

    public function authorize(): bool
    {
        return $this->isAdmin();
    }

    // if you see this comment use the methods in BaseFormRequest if needed

    public function rules(): array
    {
        return [
            'property_id' => [
                'required',
                Rule::exists('properties', 'id'),
                // Ensure this combination doesn't already exist
                Rule::unique('property_service')->where(function ($query) {
                    return $query->where('service_id', $this->input('service_id'));
                })
            ],
            'service_id' => [
                'required',
                Rule::exists('services', 'id')
            ],
            'billing_type' => [
                'required',
                Rule::in(['fixed', 'area_based', 'prepaid'])
            ],
            'price' => [
                'required',
                'numeric',
                'min:0'
            ],
            'status' => [
                'sometimes', // Use 'sometimes' so it's only validated if present
                'required', // If present, it must not be empty
                Rule::in(['active', 'inactive', 'pending_payment']) // 'expired' is usually set automatically
            ],
            'details' => [
                'nullable',
                'json'
            ],
            'activated_at' => [
                'nullable',
                'date'
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after_or_equal:activated_at' // Ensure expiry is not before activation
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'property_id.unique' => 'This service is already assigned to the specified property.',
        ];
    }
}
