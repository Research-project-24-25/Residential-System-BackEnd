<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private BillingService $billingService) {}

    public function processPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            // Create payment record
            $payment = Payment::create(array_merge($data, [
                'payment_date' => $data['payment_date'] ?? now(),
                'status' => 'paid', // Payments are created as 'paid'
            ]));

            // If payment is marked as paid, update the bill
            if ($payment->status === 'paid') { // Standardize to 'paid'
                $this->updateBillStatus($payment->bill);
            }

            return $payment;
        });
    }

    public function updatePaymentStatus(Payment $payment, string $status, ?int $processedBy = null): Payment
    {
        return DB::transaction(function () use ($payment, $status, $processedBy) {
            // Update payment status
            $payment->status = $status;

            // If admin processed this update, record their ID
            if ($processedBy) {
                $payment->processed_by = $processedBy;
            }

            $payment->save();

            // If status is paid, update the related bill
            if ($status === 'paid') { // Standardize to 'paid'
                $this->updateBillStatus($payment->bill);
            } elseif ($payment->getOriginal('status') === 'paid' && $status !== 'paid') { // Standardize to 'paid'
                // If payment was paid but is no longer paid (e.g., changed to refunded), update the bill
                $this->updateBillStatus($payment->bill);
            }

            return $payment;
        });
    }

    public function updateBillStatus(Bill $bill): void
    {
        // Force refresh to get latest data
        $bill->refresh();

        // Use billing service to update bill status
        $this->billingService->updateBill($bill, []);
    }

    public function refundPayment(Payment $payment, float $amount, string $reason, int $processedBy): Payment
    {
        return DB::transaction(function () use ($payment, $amount, $reason, $processedBy) {
            // Step 1: Update the original payment's status to 'refunded'
            // This will also trigger bill status update via updatePaymentStatus
            $this->updatePaymentStatus($payment, 'refunded', $processedBy);

            // Step 2: Create a new payment record for the refund transaction itself
            $refund = Payment::create([
                'bill_id' => $payment->bill_id,
                'resident_id' => $payment->resident_id,
                'amount' => -$amount, // Negative amount represents a refund
                'currency' => $payment->currency,
                'status' => 'paid', // The refund transaction itself is 'paid'
                'transaction_id' => 'refund_' . $payment->transaction_id, // Consider if a new unique ID is better
                'payment_date' => now(),
                'notes' => "Refund for payment #{$payment->id}. Reason: {$reason}",
                'processed_by' => $processedBy,
                'metadata' => [
                    'original_payment_id' => $payment->id,
                    'reason' => $reason,
                    'type' => 'refund'
                ]
            ]);

            // Step 3: Update original payment's metadata with specific refund details
            $originalPaymentMetadata = $payment->metadata ?? []; // $payment object was updated by updatePaymentStatus
            $originalPaymentMetadata['refunded'] = true; // Explicit flag, good for quick checks
            $originalPaymentMetadata['refund_id'] = $refund->id;
            $originalPaymentMetadata['refund_reason'] = $reason;
            $originalPaymentMetadata['refunded_amount'] = $amount; // Store the actual amount refunded
            $originalPaymentMetadata['refund_date'] = now()->format('Y-m-d H:i:s');
            $payment->metadata = $originalPaymentMetadata;
            $payment->save(); // Save original payment again with detailed refund metadata

            // Step 4: Update bill status to account for the new negative refund payment
            $this->updateBillStatus($payment->bill);

            return $refund;
        });
    }

    public function generateReceiptData(Payment $payment): array
    {
        // Load related models
        $payment->load(['bill', 'resident', 'bill.property']);

        return [
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
                'payment_date' => $payment->payment_date->format('Y-m-d H:i:s'),
            ],
            'bill' => [
                'id' => $payment->bill->id,
                'bill_type' => $payment->bill->bill_type,
                'amount' => $payment->bill->amount,
                'due_date' => $payment->bill->due_date->format('Y-m-d'),
                'description' => $payment->bill->description,
                'remaining_balance' => $payment->bill->remaining_balance,
            ],
            'property' => [
                'id' => $payment->bill->property->id,
                'label' => $payment->bill->property->label,
                'address' => $payment->bill->property->address ?? null,
            ],
            'resident' => [
                'id' => $payment->resident->id,
                'name' => $payment->resident->name,
                'email' => $payment->resident->email,
            ],
            'receipt_date' => now()->format('Y-m-d H:i:s'),
            'receipt_id' => 'RCP-' . $payment->id . '-' . time(),
        ];
    }
}
