<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeetingRequestStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated users can create meeting requests
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'property_id' => 'required|exists:properties,id',
            'requested_date' => 'required|date|after:now', // Must be in the future
            'purpose' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'id_document' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120', // Max 5MB
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
        ];
    }
}
