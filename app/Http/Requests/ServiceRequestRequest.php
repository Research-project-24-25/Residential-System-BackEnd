<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ServiceRequestRequest extends FormRequest
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

        // Rules for filtering service requests
        if ($action === 'filter') {
            return $this->getFilterRules();
        }

        // Base rules for residents creating service requests
        $rules = [
            'service_id' => ['required', 'exists:services,id'],
            'property_id' => ['required', 'exists:properties,id'],
            'description' => ['required', 'string', 'max:1000'],
            'requested_date' => ['required', 'date', 'after_or_equal:today'],
        ];

        // For residents creating a service request, check property ownership/rental
        if (!$isAdmin && !$isUpdate) {
            $rules['property_id'] = [
                'required',
                Rule::exists('property_resident', 'property_id')->where(function ($query) {
                    $query->where('resident_id', $this->user()->id);
                }),
            ];
        }

        // Admin-specific rules for creating or updating service requests
        if ($isAdmin) {
            $serviceRequestId = $this->route('id');
            $serviceRequest = \App\Models\ServiceRequest::findOrFail($serviceRequestId);
            $requested_date = $serviceRequest->requested_date ?? now()->toDateString();
            $scheduled_date = $serviceRequest->scheduled_date ?? now()->toDateString();

            $additionalRules = [
                'resident_id' => ['required', 'exists:residents,id'],
                'scheduled_date' => ['nullable', 'date', 'after_or_equal:' . $requested_date],
                'completion_date' => ['nullable', 'date', 'after_or_equal:' . $scheduled_date],
                'status' => ['sometimes', Rule::in(['pending', 'approved', 'scheduled', 'in_progress', 'completed', 'cancelled'])],
                'notes' => ['nullable', 'string', 'max:1000'],
                'estimated_cost' => ['nullable', 'numeric', 'min:0'],
                'final_cost' => ['nullable', 'numeric', 'min:0'],
                'bill_id' => ['nullable', 'exists:bills,id'],
            ];

            $rules = array_merge($rules, $additionalRules);
        }

        // For updates, make certain fields optional
        if ($isUpdate) {
            $rules['service_id'] = ['sometimes', 'exists:services,id'];
            $rules['property_id'] = ['sometimes', 'exists:properties,id'];
            $rules['description'] = ['sometimes', 'string', 'max:1000'];
            $rules['requested_date'] = ['sometimes', 'date'];

            // If it's a resident updating, they can only update the description
            if (!$isAdmin) {
                // Restrict what residents can update to just these fields
                $rules = [
                    'description' => ['sometimes', 'string', 'max:1000'],
                ];
            }
        }

        return $rules;
    }

    /**
     * Get rules for filtering service requests
     */
    private function getFilterRules(): array
    {
        return [
            'filters' => 'sometimes|array',

            // Support for single value or array of values
            'filters.status' => 'sometimes',
            'filters.status.*' => 'string|in:pending,approved,scheduled,in_progress,completed,cancelled',

            'filters.service_id' => 'sometimes|exists:services,id',
            'filters.property_id' => 'sometimes|exists:properties,id',
            'filters.resident_id' => 'sometimes|exists:residents,id',

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
