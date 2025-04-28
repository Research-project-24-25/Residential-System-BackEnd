<?php

namespace App\Http\Requests;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() instanceof Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        // Get the current route name or action to determine the context
        $action = $this->route() ? $this->route()->getActionMethod() : null;

        // Rules for filtering residents
        if ($action === 'filter') {
            return $this->getFilterRules();
        }

        // Rules for creating/updating residents
        return $this->getResidentRules();
    }

    /**
     * Get filter rules for residents
     */
    private function getFilterRules(): array
    {
        return [
            'filters' => 'sometimes|array',

            // Basic filters
            'filters.gender' => 'sometimes',
            'filters.gender.*' => 'string|in:male,female',

            // Range filters
            'filters.age' => 'sometimes|array',
            'filters.age.min' => 'sometimes|integer|min:0',
            'filters.age.max' => 'sometimes|integer|gt:filters.age.min',

            // Property relationship filters
            'filters.property_id' => 'sometimes|exists:properties,id',
            'filters.relationship_type' => 'sometimes',
            'filters.relationship_type.*' => 'string|in:buyer,co_buyer,renter',

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

    /**
     * Get resident creation/update rules
     */
    private function getResidentRules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        if ($isUpdate) {
            $residentId = $this->route('id');

            return [
                'username' => ['sometimes', 'string', 'max:255'],
                'first_name' => ['sometimes', 'string', 'max:255'],
                'last_name' => ['sometimes', 'string', 'max:255'],
                'email' => [
                    'sometimes',
                    'email',
                    Rule::unique('residents')->ignore($residentId)
                ],
                'password' => ['sometimes', 'string', 'min:8'],
                'phone_number' => ['sometimes', 'string'],
                'age' => ['sometimes', 'integer', 'min:0'],
                'gender' => ['sometimes', 'in:male,female'],
                'profile_image' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],

                'property_id' => ['sometimes', 'integer', 'exists:properties,id'],
                'relationship_type' => ['sometimes', 'in:buyer,co_buyer,renter'],
                'sale_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'ownership_share' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
                'monthly_rent' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'start_date' => ['sometimes', 'nullable', 'date'],
                'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            ];
        }

        return [
            'username' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:residents'],
            'password' => ['required', 'string', 'min:8'],
            'phone_number' => ['required', 'string'],
            'age' => ['required', 'integer', 'min:0'],
            'gender' => ['required', 'in:male,female'],
            'profile_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],

            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'relationship_type' => ['required', 'in:buyer,co_buyer,renter'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'ownership_share' => ['nullable', 'numeric', 'between:0,100'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
