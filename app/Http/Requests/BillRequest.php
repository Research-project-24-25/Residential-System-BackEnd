<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can create/update bills
        return $this->user()->getTable() === 'admins';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'property_id' => ['required', 'exists:properties,id'],
            'resident_id' => ['required', 'exists:residents,id'],
            'bill_type' => ['required', 'string', Rule::in([
                'maintenance',
                'water',
                'electricity',
                'gas',
                'internet',
                'security',
                'cleaning',
                'rent',
                'property_tax',
                'insurance',
                'other'
            ])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', Rule::in(['pending', 'partial', 'paid', 'overdue', 'cancelled'])],
            'recurrence' => ['nullable', 'string', Rule::in(['monthly', 'quarterly', 'biannual', 'annual', 'one-time'])],
            'next_billing_date' => ['nullable', 'date', 'after:due_date'],
            'metadata' => ['nullable', 'array'],
        ];

        // If we're updating a bill, make resident_id and property_id optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['property_id'] = ['nullable', 'exists:properties,id'];
            $rules['resident_id'] = ['nullable', 'exists:residents,id'];
            $rules['due_date'] = ['nullable', 'date']; // Allow updating past due dates
        }

        return $rules;
    }
}
