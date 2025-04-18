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
        // Only admins can update meeting requests
        return $this->user()->tokenCan('admin') || $this->user()->tokenCan('super_admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|required|in:pending,approved,rejected,cancelled,completed',
            'approved_date' => 'sometimes|nullable|required_if:status,approved|date|after:now',
            'admin_notes' => 'sometimes|nullable|string|max:1000',
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
        ];
    }
}
