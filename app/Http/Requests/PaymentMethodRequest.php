<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PaymentMethodRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

    public function rules(): array
    {
        $specificRules = [];
        $isAdmin = $this->isAdmin();

        $specificRules = [
            'type' => ['required', 'string', Rule::in(['credit_card', 'debit_card', 'bank_transfer', 'cash', 'check', 'wallet', 'other'])],
            'provider' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'last_four' => ['nullable', 'string', 'digits:4'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'cardholder_name' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
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

        $type = $this->input('type');
        $isPost = $this->isMethod('POST');

        if ($type === 'credit_card' || $type === 'debit_card') {
            $specificRules['provider'] = ['required', 'string', 'max:100'];
            $specificRules['last_four'] = ['required', 'string', 'digits:4'];

            if ($isPost) {
                $specificRules['account_number'] = ['required', 'string', 'max:255'];
                $specificRules['expiry_date'] = ['required', 'date_format:m/y', 'after_or_equal:today']; // Common format m/y
                $specificRules['cardholder_name'] = ['required', 'string', 'max:255'];
            }
        } elseif ($type === 'bank_transfer') {
            $specificRules['provider'] = ['required', 'string', 'max:100'];

            if ($isPost) {
                $specificRules['account_number'] = ['required', 'string', 'max:255'];
            }
        }

        return array_merge(parent::rules(), $specificRules);
    }

    public function messages(): array
    {
        $parentMessages = parent::messages();
        $specificMessages = [
            'expiry_date.date_format' => 'The expiry date must be in MM/YY format.',
        ];
        return array_merge($parentMessages, $specificMessages);
    }
}
