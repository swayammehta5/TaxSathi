<?php

namespace App\Support;

class InvoicePayload
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function flatten(array $input): array
    {
        $customer = (array) ($input['customer_details'] ?? []);
        $info = (array) ($input['invoice_info'] ?? []);
        $summary = (array) ($input['summary'] ?? []);
        $bank = (array) ($input['bank_details'] ?? []);
        $signatory = (array) ($input['signatory'] ?? []);

        $flat = array_merge($input, [
            'firm_id' => $input['firm_id'] ?? null,
            'customer_name' => $customer['customer_name'] ?? $input['customer_name'] ?? null,
            'company_name' => $customer['company_name'] ?? $input['company_name'] ?? null,
            'billing_address' => $customer['billing_address'] ?? $input['billing_address'] ?? $input['customer_address'] ?? null,
            'shipping_address' => $customer['shipping_address'] ?? $input['shipping_address'] ?? null,
            'contact_number' => $customer['contact_number'] ?? $input['contact_number'] ?? null,
            'email' => $customer['email'] ?? $input['email'] ?? null,
            'gstin' => $customer['gstin'] ?? $input['gstin'] ?? $input['gst_number'] ?? null,
            'state' => $customer['state'] ?? $input['state'] ?? null,
            'state_code' => $customer['state_code'] ?? $input['state_code'] ?? null,
            'invoice_number' => $info['invoice_number'] ?? $input['invoice_number'] ?? null,
            'date' => $info['invoice_date'] ?? $info['date'] ?? $input['date'] ?? $input['invoice_date'] ?? null,
            'due_date' => $info['due_date'] ?? $input['due_date'] ?? null,
            'po_number' => $info['po_number'] ?? $input['po_number'] ?? null,
            'delivery_challan_number' => $info['delivery_challan_number'] ?? $input['delivery_challan_number'] ?? null,
            'payment_terms' => $info['payment_terms'] ?? $input['payment_terms'] ?? null,
            'place_of_supply' => $info['place_of_supply'] ?? $input['place_of_supply'] ?? null,
            'other_charges' => $summary['other_charges'] ?? $input['other_charges'] ?? 0,
            'discount' => $summary['discount'] ?? $input['discount'] ?? 0,
            'cgst_percent' => $summary['cgst_percent'] ?? $summary['cgst'] ?? $input['cgst_percent'] ?? 9,
            'sgst_percent' => $summary['sgst_percent'] ?? $summary['sgst'] ?? $input['sgst_percent'] ?? 9,
            'igst_percent' => $summary['igst_percent'] ?? $summary['igst'] ?? $input['igst_percent'] ?? 18,
            'round_off' => $summary['round_off'] ?? $input['round_off'] ?? null,
            'tax_mode' => $summary['tax_mode'] ?? $input['tax_mode'] ?? null,
            'account_holder_name' => $bank['account_holder_name'] ?? $input['account_holder_name'] ?? null,
            'bank_name' => $bank['bank_name'] ?? $input['bank_name'] ?? null,
            'branch_name' => $bank['branch_name'] ?? $input['branch_name'] ?? null,
            'account_number' => $bank['account_number'] ?? $input['account_number'] ?? null,
            'ifsc_code' => $bank['ifsc_code'] ?? $input['ifsc_code'] ?? null,
            'upi_id' => $bank['upi_id'] ?? $input['upi_id'] ?? null,
            'authorized_person_name' => $signatory['authorized_person_name'] ?? $input['authorized_person_name'] ?? null,
            'designation' => $signatory['designation'] ?? $input['designation'] ?? null,
            'signature_data' => $signatory['signature'] ?? $signatory['signature_data'] ?? $input['signature_data'] ?? null,
            'items' => $input['items'] ?? [],
        ]);

        return $flat;
    }
}
