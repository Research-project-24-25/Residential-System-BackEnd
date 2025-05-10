<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $typeParts = explode('\\', $this->type);
        $shortType = end($typeParts);

        return [
            'id' => $this->id,
            'type' => $shortType,
            'data' => $this->data,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'message' => $this->data['message'] ?? null,
        ];
    }
}
