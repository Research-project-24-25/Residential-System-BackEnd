<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeetingRequestUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow users to update their own meeting requests
        if ($this->route('id')) {
            $meetingRequest = \App\Models\MeetingRequest::find($this->route('id'));
            if ($meetingRequest && $this->user() && $meetingRequest->user_id === $this->user()->id) {
                return true;
            }
        }

        // Also allow admins to update any meeting request
        return $this->user() && ($this->user()->getTable() === 'admins');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Different rules based on user role
        if ($this->user()->getTable() === 'admins') {
            return [
                'status' => 'sometimes|required|in:pending,approved,rejected,cancelled,completed',
                'approved_date' => 'sometimes|nullable|required_if:status,approved|date|after:now',
                'admin_notes' => 'sometimes|nullable|string|max:1000',
            ];
        }

        // Regular users can only cancel or update notes
        return [
            'notes' => 'sometimes|nullable|string|max:1000',
            'status' => 'sometimes|in:cancelled',
            'requested_date' => 'sometimes|nullable|date|after:now',
            'id_document' => 'sometimes|nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Please specify a status for the meeting request.',
            'status.in' => 'The status must be one of: pending, approved, rejected, cancelled, or completed.',
            'approved_date.required_if' => 'Please provide an approved date and time when approving a meeting request.',
            'approved_date.date' => 'Please provide a valid date and time.',
            'approved_date.after' => 'The approved date must be in the future.',
            'requested_date.date' => 'Please provide a valid date and time.',
            'requested_date.after' => 'The requested date must be in the future.',
            'requested_date.required_if' => 'Please provide a requested date and time.',
        ];
    }
}
