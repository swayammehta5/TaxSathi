<?php

namespace App\Http\Requests;

use App\Support\InvoicePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $flat = InvoicePayload::flatten($this->all());

        $items = $flat['items'] ?? [];
        if (is_array($items)) {
            $flat['items'] = collect($items)->map(function (array $item): array {
                $quantity = (float) ($item['quantity'] ?? 1);
                $rate = (float) ($item['rate'] ?? 0);

                return [
                    ...$item,
                    'quantity' => $quantity,
                    'rate' => $rate,
                    'amount' => isset($item['amount']) ? (float) $item['amount'] : round($quantity * $rate, 2),
                ];
            })->all();
        }

        $this->merge($flat);
    }

    public function rules(): array
    {
        $invoiceId = $this->route('invoice');

        return [
            'firm_id' => 'required|string|exists:firms,_id',
            'customer_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'billing_address' => 'nullable|string|max:2000',
            'shipping_address' => 'nullable|string|max:2000',
            'contact_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'gstin' => 'nullable|string|max:32',
            'state' => 'nullable|string|max:100',
            'state_code' => 'nullable|string|max:4',
            'invoice_number' => [
                'required',
                'string',
                Rule::unique('invoices', 'invoice_number')->ignore($invoiceId),
            ],
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'po_number' => 'nullable|string|max:100',
            'delivery_challan_number' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'place_of_supply' => 'nullable|string|max:255',
            'other_charges' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'cgst_percent' => 'nullable|numeric|min:0|max:100',
            'sgst_percent' => 'nullable|numeric|min:0|max:100',
            'igst_percent' => 'nullable|numeric|min:0|max:100',
            'round_off' => 'nullable|numeric',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.pl' => 'nullable|numeric|min:0',
            'items.*.th' => 'nullable|numeric|min:0',
            'items.*.hrs_set' => 'nullable|numeric|min:0',
            'items.*.total_hrs' => 'nullable|numeric|min:0',
            'items.*.hsn_sac_code' => 'nullable|string|max:32',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.amount' => 'nullable|numeric|min:0',
            'account_holder_name' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'upi_id' => 'nullable|string|max:100',
            'authorized_person_name' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'signature_data' => 'nullable|string|max:500000',
        ];
    }
}
