<?php

namespace App\Http\Requests;

use App\Models\Bill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Both residents and admins can make payments, with different rules
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

        if ($this->isMethod('POST')) {
            // Creating new payment
            $rules = [
                'bill_id' => ['required', 'exists:bills,id'],
                'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'currency' => ['nullable', 'string', 'size:3'],
                'notes' => ['nullable', 'string', 'max:500'],
                'metadata' => ['nullable', 'array'],
            ];

            // If it's a resident, they can only pay their own bills
            if ($isResident) {
                $rules['bill_id'] = [
                    'required',
                    Rule::exists('bills', 'id')->where(function ($query) {
                        $query->where('resident_id', $this->user()->id);
                    })
                ];

                // Residents can only use their own payment methods
                if ($this->filled('payment_method_id')) {
                    $rules['payment_method_id'] = [
                        'exists:payment_methods,id',
                        Rule::exists('payment_methods', 'id')->where(function ($query) {
                            $query->where('resident_id', $this->user()->id);
                        })
                    ];
                }
            }

            // If admin, allow specifying more fields
            if ($isAdmin) {
                $rules['status'] = ['nullable', Rule::in(['pending', 'processing', 'completed', 'failed', 'refunded'])];
                $rules['transaction_id'] = ['nullable', 'string', 'max:255'];
                $rules['receipt_url'] = ['nullable', 'url', 'max:255'];
                $rules['payment_date'] = ['nullable', 'date'];
                $rules['resident_id'] = ['nullable', 'exists:residents,id'];
            }

            return $rules;
        } else {
            // Updating existing payment - only admins can update payments
            if (!$isAdmin) {
                return [];
            }

            return [
                'status' => ['required', Rule::in(['pending', 'processing', 'completed', 'failed', 'refunded'])],
                'transaction_id' => ['nullable', 'string', 'max:255'],
                'receipt_url' => ['nullable', 'url', 'max:255'],
                'notes' => ['nullable', 'string', 'max:500'],
                'metadata' => ['nullable', 'array'],
            ];
        }
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bill_id.exists' => 'The selected bill does not exist or you do not have permission to pay it.',
            'payment_method_id.exists' => 'The selected payment method does not exist or does not belong to you.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
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
