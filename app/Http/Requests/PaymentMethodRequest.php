<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PaymentMethodRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Both residents and admins can manage payment methods
        return $this->isAuthenticated();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $specificRules = [];
        $isAdmin = $this->isAdmin();
        // $isResident = $this->isResident(); // Not used in current logic after isAdmin check

        $specificRules = [
            'type' => ['required', 'string', Rule::in(['credit_card', 'debit_card', 'bank_transfer', 'cash', 'check', 'wallet', 'other'])],
            'provider' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'last_four' => ['nullable', 'string', 'digits:4'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:today'], // after_or_equal is usually better for dates
            'cardholder_name' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            // 'is_verified' is handled below based on admin status
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'expired', 'cancelled'])],
            'metadata' => ['nullable', 'array'],
        ];

        if ($isAdmin) {
            $specificRules['is_verified'] = ['nullable', 'boolean'];
            if ($this->isMethod('POST')) {
                $specificRules['resident_id'] = ['required', 'exists:residents,id'];
            }
        } else {
            // If not admin, 'is_verified' cannot be set.
            // It's implicitly not in $specificRules.
            // If 'is_verified' was part of base rules and needed removal:
            // unset($specificRules['is_verified']);
        }


        // Extra validations for specific payment types
        $type = $this->input('type');
        $isPost = $this->isMethod('POST');

        if ($type === 'credit_card' || $type === 'debit_card') {
            $specificRules['provider'] = ['required', 'string', 'max:100'];
            $specificRules['last_four'] = ['required', 'string', 'digits:4'];

            if ($isPost) { // Only require on creation
                $specificRules['account_number'] = ['required', 'string', 'max:255']; // Usually tokenized, but depends on system
                $specificRules['expiry_date'] = ['required', 'date_format:m/y', 'after_or_equal:today']; // Common format m/y
                $specificRules['cardholder_name'] = ['required', 'string', 'max:255'];
            }
        } elseif ($type === 'bank_transfer') {
            $specificRules['provider'] = ['required', 'string', 'max:100']; // Bank name

            if ($isPost) { // Only require on creation
                $specificRules['account_number'] = ['required', 'string', 'max:255'];
            }
        }

        return array_merge(parent::rules(), $specificRules); // parent::rules() will be empty
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $parentMessages = parent::messages();
        $specificMessages = [
            'expiry_date.date_format' => 'The expiry date must be in MM/YY format.',
            // Add other specific messages if needed
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}
