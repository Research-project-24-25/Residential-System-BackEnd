<?php

namespace App\Http\Requests;

use App\Models\Bill;
use Illuminate\Validation\Rule;

class PaymentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST') || $this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $this->isAdmin();
        }
        return $this->isAuthenticated();
    }

    public function rules(): array
    {
        $specificRules = [];

        if ($this->isMethod('POST')) {
            $specificRules = [
                'bill_id' => ['required', 'exists:bills,id'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'currency' => ['nullable', 'string', 'size:3'],
                'notes' => ['nullable', 'string', 'max:500'],
                'metadata' => ['nullable', 'array'],
                'status' => ['nullable', Rule::in(['paid'])],
                'transaction_id' => ['nullable', 'string', 'max:255'],
                'payment_date' => ['nullable', 'date'],
                'resident_id' => ['required', 'exists:residents,id'],
            ];
        } elseif ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $specificRules = [
                'status' => ['required', Rule::in(['paid', 'refunded'])],
                'transaction_id' => ['nullable', 'string', 'max:255'],
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
