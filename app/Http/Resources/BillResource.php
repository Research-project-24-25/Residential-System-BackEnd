<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
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
            'property' => $this->whenLoaded('property', function () {
                return [
                    'id' => $this->property->id,
                    'label' => $this->property->label,
                    'type' => $this->property->type,
                ];
            }),
            'resident' => $this->whenLoaded('resident', function () {
                return [
                    'id' => $this->resident->id,
                    'name' => $this->resident->name,
                    'email' => $this->resident->email,
                ];
            }),
            'property_id' => $this->property_id,
            'resident_id' => $this->resident_id,
            'bill_type' => $this->bill_type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'due_date' => $this->due_date->format('Y-m-d'),
            'description' => $this->description,
            'status' => $this->status,
            'recurrence' => $this->recurrence,
            'next_billing_date' => $this->next_billing_date ? $this->next_billing_date->format('Y-m-d') : null,
            'paid_amount' => $this->paid_amount,
            'remaining_balance' => $this->remaining_balance,
            'is_fully_paid' => $this->is_fully_paid,
            'is_overdue' => $this->is_overdue,
            'payments' => $this->whenLoaded('payments', function () {
                return PaymentResource::collection($this->payments);
            }),
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
