<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\InvoiceItem */
class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'description' => $this->description,
            'pl' => $this->pl,
            'th' => $this->th,
            'hrs_set' => $this->hrs_set,
            'total_hrs' => $this->total_hrs,
            'hsn_sac_code' => $this->hsn_sac_code ?? $this->hsn,
            'hsn' => $this->hsn_sac_code ?? $this->hsn,
            'quantity' => $this->quantity,
            'rate' => $this->rate,
            'amount' => $this->amount,
        ];
    }
}
