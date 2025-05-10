<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Since we're passing an array directly to the resource
        // we can just return the array as is
        return $this->resource;
    }
}
