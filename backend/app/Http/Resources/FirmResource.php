<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Firm */
class FirmResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'gstin' => $this->gstin,
            'address' => $this->address,
            'state' => $this->state,
            'state_code' => $this->state_code,
            'contact_number' => $this->contact_number ?? $this->phone,
            'email' => $this->email,
            'phone' => $this->phone ?? $this->contact_number,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
