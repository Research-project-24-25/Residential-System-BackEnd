<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'resident' => $this->whenLoaded('resident', function () {
                return [
                    'id' => $this->resident->id,
                    'name' => $this->resident->name,
                    'email' => $this->resident->email,
                ];
            }),
            'resident_id' => $this->resident_id,
            'type' => $this->type,
            'provider' => $this->provider,
            'masked_account_number' => $this->masked_account_number,
            'last_four' => $this->last_four,
            'expiry_date' => $this->expiry_date ? $this->expiry_date->format('Y-m-d') : null,
            'cardholder_name' => $this->cardholder_name,
            'is_default' => $this->is_default,
            'is_verified' => $this->is_verified,
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
