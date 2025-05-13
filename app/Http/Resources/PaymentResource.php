<?php

namespace App\Http\Resources;

use App\Traits\ResourceHelpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    use ResourceHelpers;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bill' => $this->whenLoaded('bill', function () {
                return $this->handleRelation($this->bill, function ($bill) {
                    return [
                        'id' => $bill->id,
                        'bill_type' => $bill->bill_type,
                        'amount' => $bill->amount,
                        'due_date' => $bill->due_date->format('Y-m-d'),
                        'paid_amount' => $bill->paid_amount,
                        'remaining_balance' => $bill->remaining_balance,
                        'status' => $bill->status,
                    ];
                });
            }),
            'resident' => $this->whenLoaded('resident', function () {
                return $this->handleRelation($this->resident, function ($resident) {
                    return [
                        'id' => $resident->id,
                        'name' => $resident->name,
                        'email' => $resident->email,
                    ];
                });
            }),
            'bill_id' => $this->bill_id,
            'resident_id' => $this->resident_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'transaction_id' => $this->transaction_id,
            'payment_date' => $this->payment_date ? $this->payment_date->format('Y-m-d H:i:s') : null,
            'notes' => $this->notes,
            'processed_by' => $this->processed_by,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s')),
        ];
    }
}
