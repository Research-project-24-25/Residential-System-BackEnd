<?php

namespace App\Http\Requests;

use App\Models\Bill;
use Illuminate\Validation\Rule;

class PaymentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

    public function rules(): array
    {
        $specificRules = [];
        $isAdmin = $this->isAdmin();
        $isResident = $this->isResident();

        if ($this->isMethod('POST')) {
            // Creating new payment
            $specificRules = [
                'bill_id' => ['required', 'exists:bills,id'],
                'payment_method_id' => ['nullable', 'integer'], // Ensure it's an integer if provided, though it won't be validated against a table
                'amount' => ['required', 'numeric', 'min:0.01'],
                'currency' => ['nullable', 'string', 'size:3'],
                'notes' => ['nullable', 'string', 'max:500'],
                'metadata' => ['nullable', 'array'],
            ];

            // If it's a resident, they can only pay their own bills
            if ($isResident) {
                $specificRules['bill_id'] = [
                    'required',
                    Rule::exists('bills', 'id')->where(function ($query) {
                        if ($this->user()) {
                           $query->where('resident_id', $this->user()->id);
                        }
                    })
                ];

                // payment_method_id validation specific to residents is removed as payment_methods are removed
            }
    
            // If admin, allow specifying more fields
            if ($isAdmin) {
                $specificRules['status'] = ['nullable', Rule::in(['pending', 'processing', 'completed', 'failed', 'refunded'])];
                $specificRules['transaction_id'] = ['nullable', 'string', 'max:255'];
                $specificRules['receipt_url'] = ['nullable', 'url', 'max:255'];
                $specificRules['payment_date'] = ['nullable', 'date'];
                $specificRules['resident_id'] = ['nullable', 'exists:residents,id']; // Admin can specify resident
            }

        } else {
            // Updating existing payment - only admins can update payments
            if (!$isAdmin) {
                // Non-admins cannot update payments. Return empty or perhaps specific prohibitions.
                // For now, returning empty means no validation rules apply from this block for non-admins.
                // Consider if this should be an authorization failure instead.
                // For safety, let's prohibit fields if a non-admin attempts an update.
                return [
                    'status' => ['prohibited'],
                    'transaction_id' => ['prohibited'],
                    'receipt_url' => ['prohibited'],
                    'notes' => ['prohibited'],
                    'metadata' => ['prohibited'],
                ];
            }

            $specificRules = [
                'status' => ['required', Rule::in(['pending', 'processing', 'completed', 'failed', 'refunded'])],
                'transaction_id' => ['nullable', 'string', 'max:255'],
                'receipt_url' => ['nullable', 'url', 'max:255'],
                'notes' => ['nullable', 'string', 'max:500'],
                'metadata' => ['nullable', 'array'],
            ];
        }
        return array_merge(parent::rules(), $specificRules);
    }

    public function messages(): array
    {
        $parentMessages = parent::messages();
        $specificMessages = [
            'bill_id.exists' => 'The selected bill does not exist or you do not have permission to pay it.',
            // 'payment_method_id.exists' message removed
            'status.prohibited' => 'You are not authorized to update the payment status.',
        ];
        return array_merge($parentMessages, $specificMessages);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('bill_id')) {
                $bill = Bill::find($this->bill_id);

                if ($bill && $bill->is_fully_paid) {
                    $validator->errors()->add('bill_id', 'This bill has already been fully paid.');
                }

                // If payment amount exceeds remaining balance
                if ($bill && $this->filled('amount') && $this->amount > $bill->remaining_balance) {
                    $validator->errors()->add('amount', 'The payment amount exceeds the remaining balance of ' . $bill->remaining_balance);
                }
            }
        });
    }
}
