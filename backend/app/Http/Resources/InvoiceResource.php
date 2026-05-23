<?php

namespace App\Http\Resources;

use App\Support\ApiDate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Invoice */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $date = ApiDate::format($this->resource->getAttributes()['date'] ?? null);

        return [
            'id' => $this->id,
            'firm_id' => $this->firm_id,
            'customer_details' => [
                'customer_name' => $this->customer_name,
                'company_name' => $this->company_name,
                'billing_address' => $this->billing_address ?? $this->customer_address,
                'shipping_address' => $this->shipping_address,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
                'gstin' => $this->gstin ?? $this->gst_number,
                'state' => $this->state,
                'state_code' => $this->state_code,
            ],
            'invoice_info' => [
                'invoice_number' => $this->invoice_number,
                'invoice_date' => $date,
                'date' => $date,
                'due_date' => ApiDate::format($this->due_date),
                'po_number' => $this->po_number,
                'delivery_challan_number' => $this->delivery_challan_number,
                'payment_terms' => $this->payment_terms,
                'place_of_supply' => $this->place_of_supply,
            ],
            'summary' => [
                'total_job_work_amount' => $this->total_job_work_amount ?? $this->subtotal,
                'other_charges' => $this->other_charges ?? 0,
                'discount' => $this->discount ?? 0,
                'taxable_amount' => $this->taxable_amount ?? $this->subtotal,
                'cgst_percent' => $this->cgst_percent,
                'sgst_percent' => $this->sgst_percent,
                'igst_percent' => $this->igst_percent,
                'cgst' => $this->cgst,
                'sgst' => $this->sgst,
                'igst' => $this->igst,
                'round_off' => $this->round_off,
                'grand_total' => $this->total_amount,
            ],
            'bank_details' => [
                'account_holder_name' => $this->account_holder_name,
                'bank_name' => $this->bank_name,
                'branch_name' => $this->branch_name,
                'account_number' => $this->account_number,
                'ifsc_code' => $this->ifsc_code,
                'upi_id' => $this->upi_id,
            ],
            'signatory' => [
                'authorized_person_name' => $this->authorized_person_name,
                'designation' => $this->designation,
                'signature' => $this->signature_data,
            ],
            'customer_name' => $this->customer_name,
            'customer_address' => $this->billing_address ?? $this->customer_address,
            'gst_number' => $this->gstin ?? $this->gst_number,
            'direction' => $this->direction,
            'tax_mode' => $this->tax_mode,
            'invoice_number' => $this->invoice_number,
            'date' => $date,
            'subtotal' => $this->taxable_amount ?? $this->subtotal,
            'taxable_amount' => $this->taxable_amount ?? $this->subtotal,
            'cgst_percent' => $this->cgst_percent,
            'sgst_percent' => $this->sgst_percent,
            'igst_percent' => $this->igst_percent,
            'cgst' => $this->cgst,
            'sgst' => $this->sgst,
            'igst' => $this->igst,
            'round_off' => $this->round_off,
            'total_amount' => $this->total_amount,
            'total' => $this->total_amount,
            'pdf_path' => $this->pdf_path,
            'firm' => new FirmResource($this->whenLoaded('firm')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
