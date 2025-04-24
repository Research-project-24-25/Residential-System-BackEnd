<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bill' => $this->whenLoaded('bill', function () {
                return [
                    'id' => $this->bill->id,
                    'bill_type' => $this->bill->bill_type,
                    'amount' => $this->bill->amount,
                    'due_date' => $this->bill->due_date->format('Y-m-d'),
                    'status' => $this->bill->status,
                ];
            }),
            'resident' => $this->whenLoaded('resident', function () {
                return [
                    'id' => $this->resident->id,
                    'name' => $this->resident->name,
                    'email' => $this->resident->email,
                ];
            }),
            'payment_method' => $this->whenLoaded('paymentMethod', function () {
                return [
                    'id' => $this->paymentMethod->id,
                    'type' => $this->paymentMethod->type,
                    'provider' => $this->paymentMethod->provider,
                    'last_four' => $this->paymentMethod->last_four,
                ];
            }),
            'bill_id' => $this->bill_id,
            'resident_id' => $this->resident_id,
            'payment_method_id' => $this->payment_method_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'transaction_id' => $this->transaction_id,
            'receipt_url' => $this->receipt_url,
            'payment_date' => $this->payment_date ? $this->payment_date->format('Y-m-d H:i:s') : null,
            'notes' => $this->notes,
            'processed_by' => $this->processed_by,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
