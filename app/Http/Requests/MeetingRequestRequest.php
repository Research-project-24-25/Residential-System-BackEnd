<?php

namespace App\Http\Requests;

use App\Models\MeetingRequest as MeetingRequestModel; // Alias for clarity

class MeetingRequestRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            // Authorization for creating a meeting request (Store)
            return $this->isAuthenticated();
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // Authorization for updating a meeting request (Update)
            if (!$this->isAuthenticated()) {
                return false;
            }

            $meetingRequestId = $this->route('id') ?? $this->route('meeting_request');
            if ($meetingRequestId) {
                $meetingRequest = MeetingRequestModel::find($meetingRequestId);
                if ($meetingRequest && $this->user()->id === $meetingRequest->user_id) {
                    return true;
                }
            }
            return $this->isAdmin();
        }

        return false; // Default to false if not POST, PUT, or PATCH
    }

    public function rules(): array
    {
        $specificRules = [];

        if ($this->isMethod('POST')) {
            // Rules for creating a meeting request (Store)
            $specificRules = [
                'property_id' => ['required', 'exists:properties,id'],
                'requested_date' => ['required', 'date', 'after:now'],
                'purpose' => ['required', 'string', 'max:500'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'id_document' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            ];
        } elseif ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // Rules for updating a meeting request (Update)
            if ($this->isAdmin()) {
                $specificRules = [
                    'status' => ['sometimes', 'required', 'in:pending,approved,rejected,cancelled,completed'],
                    'approved_date' => ['sometimes', 'nullable', 'required_if:status,approved', 'date', 'after:now'],
                    'admin_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
                    // Admin can also update fields regular users can, if needed
                    'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
                    'requested_date' => ['sometimes', 'nullable', 'date', 'after:now'],
                    'id_document' => ['sometimes', 'nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
                ];
            } else if ($this->isAuthenticated()) { // Non-admins
                $specificRules = [
                    'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
                    'status' => ['sometimes', 'in:cancelled'], // Can only cancel
                    'requested_date' => ['sometimes', 'nullable', 'date', 'after:now'],
                    'id_document' => ['sometimes', 'nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
                ];
            }
        }
        return array_merge(parent::rules(), $specificRules);
    }

    public function messages(): array
    {
        $parentMessages = parent::messages();
        $specificMessages = [
            // Store messages
            'property_id.required' => 'Please select a property for the meeting.',
            'property_id.exists' => 'The selected property does not exist.',
            'requested_date.required' => 'Please select a date and time for the meeting.',
            'requested_date.date' => 'Please provide a valid date and time.',
            'requested_date.after' => 'The meeting date must be in the future.',
            'purpose.required' => 'Please provide a purpose for the meeting.',
            'id_document.required' => 'Please upload a copy of your ID document.',
            'id_document.file' => 'The ID document must be a file.',
            'id_document.mimes' => 'The ID document must be a JPEG, PNG, or PDF file.',
            'id_document.max' => 'The ID document may not be larger than 5MB.',

            // Update messages (some might overlap with store, which is fine)
            'status.required' => 'Please specify a status for the meeting request.',
            'status.in' => 'The status must be one of: pending, approved, rejected, cancelled, or completed. For users, only "cancelled" is allowed if changing status.',
            'approved_date.required_if' => 'Please provide an approved date and time when approving a meeting request.',
            'approved_date.date' => 'Please provide a valid date and time for approval.',
            'approved_date.after' => 'The approved date must be in the future.',
            // 'requested_date.required_if' // This was commented out in original, keeping it so
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}