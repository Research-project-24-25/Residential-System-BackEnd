<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentService
{
    public function __construct(private BillingService $billingService) {}

    /**
     * Process a new payment
     *
     * @param array $data
     * @return Payment
     * @throws \Exception
     */
    public function processPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            // Create payment record
            $payment = Payment::create(array_merge($data, [
                'payment_date' => $data['payment_date'] ?? now(),
                'status' => $data['status'] ?? 'pending',
            ]));

            // If payment is marked as completed, update the bill
            if ($payment->status === 'completed') {
                $this->updateBillStatus($payment);
            }

            return $payment;
        });
    }

    /**
     * Update a payment's status
     *
     * @param Payment $payment
     * @param string $status
     * @param int|null $processedBy
     * @return Payment
     */
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

            // If status is completed, update the related bill
            if ($status === 'completed') {
                $this->updateBillStatus($payment);
            } elseif ($payment->getOriginal('status') === 'completed' && $status !== 'completed') {
                // If payment was completed but is no longer completed, reverse the bill update
                $this->reversePayment($payment);
            }

            return $payment;
        });
    }

    /**
     * Update bill status after a payment is made or updated
     *
     * @param Payment $payment
     * @return void
     */
    private function updateBillStatus(Payment $payment): void
    {
        $bill = $payment->bill;

        // Force refresh to get latest data
        $bill->refresh();

        // Use billing service to update bill status
        $this->billingService->updateBill($bill, []);
    }

    /**
     * Reverse a payment's impact on a bill (when payment status changes from completed)
     *
     * @param Payment $payment
     * @return void
     */
    private function reversePayment(Payment $payment): void
    {
        $bill = $payment->bill;

        // Force refresh to get latest data
        $bill->refresh();

        // Update bill status based on remaining payments
        $this->billingService->updateBill($bill, []);
    }

    /**
     * Record a refund for a payment
     *
     * @param Payment $payment
     * @param float $amount
     * @param string $reason
     * @param int $processedBy
     * @return Payment
     */
    public function refundPayment(Payment $payment, float $amount, string $reason, int $processedBy): Payment
    {
        return DB::transaction(function () use ($payment, $amount, $reason, $processedBy) {
            // Create a refund record
            $refund = Payment::create([
                'bill_id' => $payment->bill_id,
                'resident_id' => $payment->resident_id,
                'payment_method_id' => $payment->payment_method_id,
                'amount' => -$amount, // Negative amount represents a refund
                'currency' => $payment->currency,
                'status' => 'completed',
                'transaction_id' => 'refund_' . $payment->transaction_id,
                'payment_date' => now(),
                'notes' => "Refund for payment #{$payment->id}. Reason: {$reason}",
                'processed_by' => $processedBy,
                'metadata' => [
                    'original_payment_id' => $payment->id,
                    'reason' => $reason,
                    'type' => 'refund'
                ]
            ]);

            // Update original payment metadata to reference the refund
            $metadata = $payment->metadata ?? [];
            $metadata['refunded'] = true;
            $metadata['refund_id'] = $refund->id;
            $metadata['refund_reason'] = $reason;
            $metadata['refund_date'] = now()->format('Y-m-d H:i:s');

            $payment->metadata = $metadata;
            $payment->save();

            // Update bill status
            $this->updateBillStatus($payment);

            return $refund;
        });
    }

    /**
     * Generate payment receipt data
     *
     * @param Payment $payment
     * @return array
     */
    public function generateReceiptData(Payment $payment): array
    {
        // Load related models
        $payment->load(['bill', 'resident', 'paymentMethod', 'bill.property']);

        return [
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
                'payment_date' => $payment->payment_date->format('Y-m-d H:i:s'),
                'payment_method' => [
                    'type' => $payment->paymentMethod->type,
                    'provider' => $payment->paymentMethod->provider,
                    'last_four' => $payment->paymentMethod->last_four,
                ]
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
