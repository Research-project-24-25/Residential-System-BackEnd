<?php

namespace App\Http\Requests;

use App\Models\MaintenanceRequest as MaintenanceRequestModel;

class MaintenanceFeedbackRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow residents to submit feedback for their own maintenance requests
        if ($this->route('maintenanceRequestId')) {
            $maintenanceRequest = MaintenanceRequestModel::find($this->route('maintenanceRequestId'));
            return $maintenanceRequest &&
                $this->isAuthenticated() && // Ensures user is not null
                $this->isResident() && // Uses helper
                $maintenanceRequest->resident_id === $this->user()->id;
        }

        return false; // Default to false if no maintenanceRequestId
    }

    public function rules(): array
    {
        // Base rules for creating feedback
        $specificRules = [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'improvement_suggestions' => ['nullable', 'string', 'max:1000'],
            'resolved_satisfactorily' => ['required', 'boolean'],
            'would_recommend' => ['required', 'boolean'],
        ];

        // For updates, make all fields optional
        if ($this->isUpdateRequest()) {
            $specificRules['rating'] = ['sometimes', 'integer', 'min:1', 'max:5'];
            // comments and improvement_suggestions are already nullable, 'sometimes' is not strictly needed
            // but doesn't hurt if we want to ensure they are only processed if present in update.
            $specificRules['comments'] = ['sometimes', 'nullable', 'string', 'max:1000'];
            $specificRules['improvement_suggestions'] = ['sometimes', 'nullable', 'string', 'max:1000'];
            $specificRules['resolved_satisfactorily'] = ['sometimes', 'boolean'];
            $specificRules['would_recommend'] = ['sometimes', 'boolean'];
        }

        return array_merge(parent::rules(), $specificRules); // parent::rules() will be empty here
    }

    public function messages(): array
    {
        $parentMessages = parent::messages(); // parent::messages() will be empty here
        $specificMessages = [
            'rating.required' => 'Please provide a rating for the maintenance service.',
            'rating.integer' => 'Rating must be a number from 1 to 5.',
            'rating.min' => 'Rating must be at least 1.',
            'rating.max' => 'Rating cannot be more than 5.',
            'resolved_satisfactorily.required' => 'Please indicate if your issue was resolved satisfactorily.',
            'would_recommend.required' => 'Please indicate if you would recommend our maintenance service.',
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}
