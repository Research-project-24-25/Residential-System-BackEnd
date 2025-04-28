<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Both residents and admins can manage payment methods
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $isAdmin = $this->user()->getTable() === 'admins';
        $isResident = $this->user()->getTable() === 'residents';

        $rules = [
            'type' => ['required', 'string', Rule::in(['credit_card', 'debit_card', 'bank_transfer', 'cash', 'check', 'wallet', 'other'])],
            'provider' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'last_four' => ['nullable', 'string', 'digits:4'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'cardholder_name' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'expired', 'cancelled'])],
            'metadata' => ['nullable', 'array'],
        ];

        // Only admins can set a payment method as verified
        if (!$isAdmin) {
            unset($rules['is_verified']);
        }

        // If it's an admin creating a payment method for a resident
        if ($isAdmin && $this->isMethod('POST')) {
            $rules['resident_id'] = ['required', 'exists:residents,id'];
        }

        // Extra validations for specific payment types
        if ($this->input('type') === 'credit_card' || $this->input('type') === 'debit_card') {
            $rules['provider'] = ['required', 'string', 'max:100'];
            $rules['last_four'] = ['required', 'string', 'digits:4'];

            if ($this->isMethod('POST')) { // Only require on creation
                $rules['account_number'] = ['required', 'string', 'max:255'];
                $rules['expiry_date'] = ['required', 'date', 'after:today'];
                $rules['cardholder_name'] = ['required', 'string', 'max:255'];
            }
        } elseif ($this->input('type') === 'bank_transfer') {
            $rules['provider'] = ['required', 'string', 'max:100']; // Bank name

            if ($this->isMethod('POST')) { // Only require on creation
                $rules['account_number'] = ['required', 'string', 'max:255'];
            }
        }

        return $rules;
    }
}
