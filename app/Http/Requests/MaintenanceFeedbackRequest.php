<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow residents to submit feedback for their own maintenance requests
        if ($this->route('maintenanceRequestId')) {
            $maintenanceRequest = \App\Models\MaintenanceRequest::find($this->route('maintenanceRequestId'));
            return $maintenanceRequest &&
                $this->user() &&
                $this->user()->getTable() === 'residents' &&
                $maintenanceRequest->resident_id === $this->user()->id;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        // Base rules for creating feedback
        $rules = [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'improvement_suggestions' => ['nullable', 'string', 'max:1000'],
            'resolved_satisfactorily' => ['required', 'boolean'],
            'would_recommend' => ['required', 'boolean'],
        ];

        // For updates, make all fields optional
        if ($isUpdate) {
            $rules['rating'] = ['sometimes', 'integer', 'min:1', 'max:5'];
            $rules['resolved_satisfactorily'] = ['sometimes', 'boolean'];
            $rules['would_recommend'] = ['sometimes', 'boolean'];
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
        return [
            'rating.required' => 'Please provide a rating for the maintenance service.',
            'rating.integer' => 'Rating must be a number from 1 to 5.',
            'rating.min' => 'Rating must be at least 1.',
            'rating.max' => 'Rating cannot be more than 5.',
            'resolved_satisfactorily.required' => 'Please indicate if your issue was resolved satisfactorily.',
            'would_recommend.required' => 'Please indicate if you would recommend our maintenance service.',
        ];
    }
}
