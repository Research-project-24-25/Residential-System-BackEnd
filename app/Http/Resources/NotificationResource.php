<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Extract notification type from full class name for cleaner response
        $typeParts = explode('\\', $this->type);
        $shortType = end($typeParts);

        return [
            'id' => $this->id,
            'type' => $shortType,
            'data' => $this->data,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Add a user-friendly message from the data if available
            'message' => $this->data['message'] ?? null,
        ];
    }
}
