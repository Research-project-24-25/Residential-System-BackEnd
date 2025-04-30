<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MeetingRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'property' => new PropertyResource($this->whenLoaded('property')),
            'requested_date' => $this->requested_date,
            'purpose' => $this->purpose,
            'notes' => $this->notes,
            'status' => $this->status,
            'approved_date' => $this->approved_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Include admin data if user is an admin
        if ($request->user() && ($request->user()->tokenCan('admin') || $request->user()->tokenCan('super_admin'))) {
            $data['user'] = new UserResource($this->whenLoaded('user'));
            $data['admin_notes'] = $this->admin_notes;
            $data['admin'] = new AdminResource($this->whenLoaded('admin'));
            $data['id_document'] = $this->id_document ? Storage::url($this->id_document) : null;
        }

        return $data;
    }
}