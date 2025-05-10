<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class BillRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can create/update bills
        return $this->isAdmin();
    }

    public function rules(): array
    {
        $specificRules = [
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
        if ($this->isUpdateRequest()) {
            $specificRules['property_id'] = ['nullable', 'exists:properties,id'];
            $specificRules['resident_id'] = ['nullable', 'exists:residents,id'];
            $specificRules['due_date'] = ['nullable', 'date']; // Allow updating past due dates
        }

        return array_merge(parent::rules(), $specificRules);
    }
}
