<?php

namespace App\Http\Requests;

use App\Models\MeetingRequest as MeetingRequestModel; // Alias for clarity

class MeetingRequestUpdateRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        // Allow users to update their own meeting requests
        // Ensure route('id') is the correct parameter name for the meeting request ID
        $meetingRequestId = $this->route('id') ?? $this->route('meeting_request'); // Common alternatives
        if ($meetingRequestId) {
            $meetingRequest = MeetingRequestModel::find($meetingRequestId);
            if ($meetingRequest && $this->user()->id === $meetingRequest->user_id) {
                return true;
            }
        }

        // Also allow admins to update any meeting request
        return $this->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $specificRules = [];
        // Different rules based on user role
        if ($this->isAdmin()) {
            $specificRules = [
                'status' => ['sometimes', 'required', 'in:pending,approved,rejected,cancelled,completed'],
                'approved_date' => ['sometimes', 'nullable', 'required_if:status,approved', 'date', 'after:now'],
                'admin_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            ];
        } else if ($this->isAuthenticated()) { // Assuming non-admins are authenticated users
            // Regular users can update limited fields
            $specificRules = [
                'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
                // Allow user to cancel their request, or admin to set other statuses
                'status' => ['sometimes', 'in:cancelled'],
                'requested_date' => ['sometimes', 'nullable', 'date', 'after:now'],
                'id_document' => ['sometimes', 'nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            ];
        }
        return array_merge(parent::rules(), $specificRules); // parent::rules() will be empty
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $parentMessages = parent::messages(); // parent::messages() will be empty
        $specificMessages = [
            'status.required' => 'Please specify a status for the meeting request.',
            'status.in' => 'The status must be one of: pending, approved, rejected, cancelled, or completed. For users, only "cancelled" is allowed if changing status.',
            'approved_date.required_if' => 'Please provide an approved date and time when approving a meeting request.',
            'approved_date.date' => 'Please provide a valid date and time for approval.',
            'approved_date.after' => 'The approved date must be in the future.',
            'requested_date.date' => 'Please provide a valid requested date and time.',
            'requested_date.after' => 'The requested date must be in the future.',
            // 'requested_date.required_if' => 'Please provide a requested date and time.', // This might be too restrictive for updates
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}
