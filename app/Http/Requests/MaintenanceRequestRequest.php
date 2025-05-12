<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class MaintenanceRequestRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $parentRules = parent::rules();

        if ($this->isFilterAction()) {
            return array_merge($parentRules, $this->getSpecificFilterRules());
        }

        // Entity specific rules for create/update
        $specificRules = $this->getEntityRules();
        return array_merge($parentRules, $specificRules); // parentRules will be empty if not filter action
    }

    /**
     * Get entity specific rules for creating/updating maintenance requests.
     */
    private function getEntityRules(): array
    {
        $isAdmin = $this->isAdmin();
        $isUpdate = $this->isUpdateRequest();

        // Base rules for residents creating maintenance requests
        $rules = [
            'maintenance_id' => ['nullable', 'exists:maintenances,id'],
            'property_id' => ['required', 'exists:properties,id'],
            'description' => ['required', 'string', 'max:1000'],
            'issue_details' => ['nullable', 'string', 'max:5000'],
            'priority' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high', 'emergency'])],
            'requested_date' => ['required', 'date'],
            'images' => ['sometimes', 'nullable'],
            'images.*' => ['sometimes', 'file', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
        ];

        // For residents creating a maintenance request, check property ownership/rental
        if (!$isAdmin && !$isUpdate) {
            $rules['property_id'] = [
                'required',
                Rule::exists('property_resident', 'property_id')->where(function ($query) {
                    if ($this->user()) { // Ensure user is available
                        $query->where('resident_id', $this->user()->id);
                    }
                }),
            ];
        }

        // Admin-specific rules for creating or updating maintenance requests
        if ($isAdmin) {
            $additionalRules = [
                'scheduled_date' => ['nullable', 'date'],
                'completion_date' => ['nullable', 'date'],
                'status' => ['sometimes', Rule::in(['pending', 'approved', 'scheduled', 'in_progress', 'completed', 'cancelled'])],
                'notes' => ['nullable', 'string', 'max:1000'],
                'estimated_cost' => ['nullable', 'numeric', 'min:0'],
                'final_cost' => ['nullable', 'numeric', 'min:0'],
                'bill_id' => ['nullable', 'exists:bills,id'],
                'has_feedback' => ['sometimes', 'boolean'],
            ];
            $rules = array_merge($rules, $additionalRules);
        }

        // For updates, make certain fields optional
        if ($isUpdate) {
            $rules['maintenance_id'] = ['sometimes', 'nullable', 'exists:maintenances,id'];
            $rules['property_id'] = ['sometimes', 'exists:properties,id']; // Admin can change property_id
            $rules['description'] = ['sometimes', 'string', 'max:1000'];
            $rules['requested_date'] = ['sometimes', 'date'];

            // If it's a resident updating, they can only update limited fields
            if (!$isAdmin) {
                $rules = [ // Override rules for resident update
                    'description' => ['sometimes', 'string', 'max:1000'],
                    'issue_details' => ['sometimes', 'string', 'max:5000'],
                    'images' => ['sometimes', 'nullable'],
                    'images.*' => ['sometimes', 'file', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
                ];
                 // Ensure other fields are not submittable by residents on update
                $rules['maintenance_id'] = ['prohibited'];
                $rules['property_id'] = ['prohibited'];
                $rules['priority'] = ['prohibited'];
                $rules['requested_date'] = ['prohibited'];
            }
        }
        return $rules;
    }

    private function getSpecificFilterRules(): array
    {
        return [
            // Support for single value or array of values
            'filters.status' => ['sometimes', 'nullable'],
            'filters.status.*' => ['string', Rule::in(['pending', 'approved', 'scheduled', 'in_progress', 'completed', 'cancelled'])],

            'filters.priority' => ['sometimes', 'nullable'],
            'filters.priority.*' => ['string', Rule::in(['low', 'medium', 'high', 'emergency'])],

            'filters.maintenance_id' => ['sometimes', 'nullable', 'exists:maintenances,id'],
            'filters.property_id' => ['sometimes', 'nullable', 'exists:properties,id'],
            'filters.resident_id' => ['sometimes', 'nullable', 'exists:residents,id'],
            'filters.has_feedback' => ['sometimes', 'boolean'],

            // Date filters (specific to this request beyond created_at/updated_at)
            'filters.requested_date' => ['sometimes', 'array'],
            'filters.requested_date.from' => ['sometimes', 'nullable', 'date'],
            'filters.requested_date.to' => ['sometimes', 'nullable', 'date', 'after_or_equal:filters.requested_date.from'],

            'filters.scheduled_date' => ['sometimes', 'array'],
            'filters.scheduled_date.from' => ['sometimes', 'nullable', 'date'],
            'filters.scheduled_date.to' => ['sometimes', 'nullable', 'date', 'after_or_equal:filters.scheduled_date.from'],

            'filters.completion_date' => ['sometimes', 'array'],
            'filters.completion_date.from' => ['sometimes', 'nullable', 'date'],
            'filters.completion_date.to' => ['sometimes', 'nullable', 'date', 'after_or_equal:filters.completion_date.from'],
        ];
    }

    public function messages(): array
    {
        $parentMessages = parent::messages();
        $specificMessages = [
            // Add any specific messages if needed
            'property_id.exists' => 'The selected property is invalid or you do not have access to it for this request.',
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}
