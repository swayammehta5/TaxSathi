<?php

namespace App\Services;

use App\Models\Firm;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\InvoicePayload;

class InvoiceService
{
    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeLineItem(array $item): array
    {
        $quantity = (float) ($item['quantity'] ?? 1);
        $rate = (float) ($item['rate'] ?? 0);
        $amount = isset($item['amount'])
            ? (float) $item['amount']
            : round($quantity * $rate, 2);

        return [
            'description' => (string) ($item['description'] ?? ''),
            'pl' => isset($item['pl']) ? (float) $item['pl'] : null,
            'th' => isset($item['th']) ? (float) $item['th'] : null,
            'hrs_set' => isset($item['hrs_set']) ? (float) $item['hrs_set'] : null,
            'total_hrs' => isset($item['total_hrs']) ? (float) $item['total_hrs'] : null,
            'hsn_sac_code' => $item['hsn_sac_code'] ?? $item['hsn'] ?? null,
            'hsn' => $item['hsn_sac_code'] ?? $item['hsn'] ?? null,
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => round($amount, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{lines: array<int, array<string, mixed>>, summary: array<string, mixed>, tax_mode: string}
     */
    private function buildTotals(array $payload): array
    {
        $itemsPayload = $payload['items'] ?? [];
        $lines = [];
        $totalJobWork = 0.0;

        foreach ($itemsPayload as $item) {
            $line = $this->normalizeLineItem($item);
            $totalJobWork += $line['amount'];
            $lines[] = $line;
        }

        $firm = Firm::query()->find($payload['firm_id']);
        $taxMode = $payload['tax_mode']
            ?? GstCalculator::detectTaxMode(
                $firm?->gstin,
                $payload['gstin'] ?? null,
                $payload['state_code'] ?? null
            );

        $summary = GstCalculator::computeJobWorkSummary(
            $totalJobWork,
            (float) ($payload['other_charges'] ?? 0),
            (float) ($payload['discount'] ?? 0),
            $taxMode,
            [
                'cgst_percent' => $payload['cgst_percent'] ?? 9,
                'sgst_percent' => $payload['sgst_percent'] ?? 9,
                'igst_percent' => $payload['igst_percent'] ?? 18,
            ],
            isset($payload['round_off']) ? (float) $payload['round_off'] : null
        );

        return [
            'lines' => $lines,
            'summary' => $summary,
            'tax_mode' => $taxMode,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function invoiceAttributes(array $payload, array $summary, string $taxMode): array
    {
        return [
            'firm_id' => $payload['firm_id'],
            'customer_name' => $payload['customer_name'],
            'company_name' => $payload['company_name'] ?? null,
            'billing_address' => $payload['billing_address'] ?? null,
            'shipping_address' => $payload['shipping_address'] ?? null,
            'contact_number' => $payload['contact_number'] ?? null,
            'email' => $payload['email'] ?? null,
            'gstin' => $payload['gstin'] ?? null,
            'gst_number' => $payload['gstin'] ?? null,
            'state' => $payload['state'] ?? null,
            'state_code' => $payload['state_code'] ?? null,
            'customer_address' => $payload['billing_address'] ?? null,
            'invoice_number' => $payload['invoice_number'],
            'date' => $payload['date'],
            'due_date' => $payload['due_date'] ?? null,
            'po_number' => $payload['po_number'] ?? null,
            'delivery_challan_number' => $payload['delivery_challan_number'] ?? null,
            'payment_terms' => $payload['payment_terms'] ?? null,
            'place_of_supply' => $payload['place_of_supply'] ?? null,
            'direction' => $payload['direction'] ?? 'outbound',
            'tax_mode' => $taxMode,
            'total_job_work_amount' => $summary['total_job_work_amount'],
            'other_charges' => $summary['other_charges'],
            'discount' => $summary['discount'],
            'taxable_amount' => $summary['taxable_amount'],
            'subtotal' => $summary['taxable_amount'],
            'cgst_percent' => $summary['cgst_percent'],
            'sgst_percent' => $summary['sgst_percent'],
            'igst_percent' => $summary['igst_percent'],
            'cgst' => $summary['cgst'],
            'sgst' => $summary['sgst'],
            'igst' => $summary['igst'],
            'round_off' => $summary['round_off'],
            'total_amount' => $summary['total_amount'],
            'account_holder_name' => $payload['account_holder_name'] ?? null,
            'bank_name' => $payload['bank_name'] ?? null,
            'branch_name' => $payload['branch_name'] ?? null,
            'account_number' => $payload['account_number'] ?? null,
            'ifsc_code' => $payload['ifsc_code'] ?? null,
            'upi_id' => $payload['upi_id'] ?? null,
            'authorized_person_name' => $payload['authorized_person_name'] ?? null,
            'designation' => $payload['designation'] ?? null,
            'signature_data' => $payload['signature_data'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): Invoice
    {
        $payload = InvoicePayload::flatten($input);
        $built = $this->buildTotals($payload);

        $invoice = Invoice::query()->create(
            $this->invoiceAttributes($payload, $built['summary'], $built['tax_mode'])
        );

        foreach ($built['lines'] as $line) {
            $line['invoice_id'] = $invoice->id;
            InvoiceItem::query()->create($line);
        }

        return $invoice->fresh(['firm', 'items']);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Invoice $invoice, array $input): Invoice
    {
        $payload = InvoicePayload::flatten($input);
        $built = $this->buildTotals($payload);

        $invoice->update(
            $this->invoiceAttributes($payload, $built['summary'], $built['tax_mode'])
        );

        $invoice->items()->delete();
        foreach ($built['lines'] as $line) {
            $line['invoice_id'] = $invoice->id;
            InvoiceItem::query()->create($line);
        }

        return $invoice->fresh(['firm', 'items']);
    }
}
