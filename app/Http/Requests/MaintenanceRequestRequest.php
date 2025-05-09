<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceRequestRequest extends FormRequest
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

        // Rules for filtering maintenance requests
        if ($action === 'filter') {
            return $this->getFilterRules();
        }

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
                    $query->where('resident_id', $this->user()->id);
                }),
            ];
        }

        // Admin-specific rules for creating or updating maintenance requests
        if ($isAdmin) {
            $additionalRules = [
                'resident_id' => ['required', 'exists:residents,id'],
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
            $rules['property_id'] = ['sometimes', 'exists:properties,id'];
            $rules['description'] = ['sometimes', 'string', 'max:1000'];
            $rules['requested_date'] = ['sometimes', 'date'];

            // If it's a resident updating, they can only update the description and issue details
            if (!$isAdmin) {
                // Restrict what residents can update to just these fields
                $rules = [
                    'description' => ['sometimes', 'string', 'max:1000'],
                    'issue_details' => ['sometimes', 'string', 'max:5000'],
                    'images' => ['sometimes', 'nullable'],
                    'images.*' => ['sometimes', 'file', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
                ];
            }
        }

        return $rules;
    }

    /**
     * Get rules for filtering maintenance requests
     */
    private function getFilterRules(): array
    {
        return [
            'filters' => 'sometimes|array',

            // Support for single value or array of values
            'filters.status' => 'sometimes',
            'filters.status.*' => 'string|in:pending,approved,scheduled,in_progress,completed,cancelled',

            'filters.priority' => 'sometimes',
            'filters.priority.*' => 'string|in:low,medium,high,emergency',

            'filters.maintenance_id' => 'sometimes|exists:maintenances,id',
            'filters.property_id' => 'sometimes|exists:properties,id',
            'filters.resident_id' => 'sometimes|exists:residents,id',
            'filters.has_feedback' => 'sometimes|boolean',

            // Date filters
            'filters.requested_date' => 'sometimes|array',
            'filters.requested_date.from' => 'sometimes|date',
            'filters.requested_date.to' => 'sometimes|date|after_or_equal:filters.requested_date.from',

            'filters.scheduled_date' => 'sometimes|array',
            'filters.scheduled_date.from' => 'sometimes|date',
            'filters.scheduled_date.to' => 'sometimes|date|after_or_equal:filters.scheduled_date.from',

            'filters.completion_date' => 'sometimes|array',
            'filters.completion_date.from' => 'sometimes|date',
            'filters.completion_date.to' => 'sometimes|date|after_or_equal:filters.completion_date.from',

            'filters.created_at' => 'sometimes|array',
            'filters.created_at.from' => 'sometimes|date',
            'filters.created_at.to' => 'sometimes|date|after_or_equal:filters.created_at.from',

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
