<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; margin: 16px; }
        .title { text-align: center; font-size: 16px; font-weight: bold; background: #1e293b; color: #fff; padding: 8px; margin-bottom: 12px; }
        .grid2 { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .grid2 td { width: 50%; vertical-align: top; border: 1px solid #cbd5e1; padding: 8px; }
        .label { font-size: 8px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 4px; }
        .meta { width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; margin-bottom: 10px; }
        .meta td { padding: 3px 8px; border: 1px solid #e2e8f0; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.items th, table.items td { border: 1px solid #cbd5e1; padding: 5px; }
        table.items th { background: #f1f5f9; font-size: 9px; }
        .bottom { width: 100%; border-collapse: collapse; }
        .bottom td { vertical-align: top; width: 50%; }
        .bank, .summary { border: 1px solid #cbd5e1; padding: 8px; }
        .summary p { margin: 3px 0; text-align: right; }
        .grand { font-size: 12px; font-weight: bold; border-top: 2px solid #1e293b; padding-top: 6px; margin-top: 6px; }
        .declaration { border: 1px solid #cbd5e1; padding: 8px; margin: 10px 0; font-size: 9px; }
        .sign { text-align: right; margin-top: 16px; }
        .sign img { height: 50px; max-width: 180px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="title">TAX INVOICE</div>
    @if($invoice->relationLoaded('firm') && $invoice->firm)
        <p style="text-align:center;font-weight:bold;margin-bottom:8px;">{{ $invoice->firm->name }}</p>
    @endif

    <table class="grid2">
        <tr>
            <td>
                <div class="label">Seller</div>
                @if($invoice->firm)
                    <strong>{{ $invoice->firm->name }}</strong><br>
                    GSTIN: {{ $invoice->firm->gstin ?? 'N/A' }}<br>
                    @if($invoice->firm->address){{ $invoice->firm->address }}<br>@endif
                    @if($invoice->firm->phone){{ $invoice->firm->phone }}@endif
                @else
                    —
                @endif
            </td>
            <td>
                <div class="label">Customer</div>
                <strong>{{ $invoice->customer_name ?? '—' }}</strong><br>
                @if($invoice->company_name){{ $invoice->company_name }}<br>@endif
                @if($invoice->billing_address){{ $invoice->billing_address }}<br>@endif
                @if($invoice->shipping_address)<strong>Ship:</strong> {{ $invoice->shipping_address }}<br>@endif
                @if($invoice->contact_number)Ph: {{ $invoice->contact_number }}<br>@endif
                @if($invoice->email){{ $invoice->email }}<br>@endif
                @if($invoice->gstin)GSTIN: {{ $invoice->gstin }}<br>@endif
                @if($invoice->state)
                    {{ $invoice->state }}@if($invoice->state_code) ({{ $invoice->state_code }})@endif
                @endif
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</td>
            <td><strong>Date:</strong> {{ $invoice->date }}</td>
            <td><strong>Due:</strong> {{ $invoice->due_date ?? '—' }}</td>
            <td><strong>PO:</strong> {{ $invoice->po_number ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Challan:</strong> {{ $invoice->delivery_challan_number ?? '—' }}</td>
            <td colspan="2"><strong>Payment:</strong> {{ $invoice->payment_terms ?? '—' }}</td>
            <td><strong>Place of supply:</strong> {{ $invoice->place_of_supply ?? '—' }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Description</th>
                <th>PL</th>
                <th>TH</th>
                <th>HRS/Set</th>
                <th>Total HRS</th>
                <th>HSN/SAC</th>
                <th>Qty</th>
                <th>Rate</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items ?? [] as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ $item->pl ?? '—' }}</td>
                <td class="text-center">{{ $item->th ?? '—' }}</td>
                <td class="text-center">{{ $item->hrs_set ?? '—' }}</td>
                <td class="text-center">{{ $item->total_hrs ?? '—' }}</td>
                <td>{{ $item->hsn_sac_code ?? $item->hsn ?? '—' }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format((float) ($item->rate ?? 0), 2) }}</td>
                <td class="text-right">{{ number_format((float) ($item->amount ?? 0), 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center">No line items</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="bottom">
        <tr>
            <td style="padding-right:8px;">
                <div class="bank">
                    <div class="label">Bank Details</div>
                    <p>A/c Holder: {{ $invoice->account_holder_name ?? '—' }}</p>
                    <p>Bank: {{ $invoice->bank_name ?? '—' }}</p>
                    <p>Branch: {{ $invoice->branch_name ?? '—' }}</p>
                    <p>A/c No: {{ $invoice->account_number ?? '—' }}</p>
                    <p>IFSC: {{ $invoice->ifsc_code ?? '—' }}</p>
                    @if($invoice->upi_id)<p>UPI: {{ $invoice->upi_id }}</p>@endif
                </div>
            </td>
            <td>
                <div class="summary">
                    <p>Total Job Work: Rs. {{ number_format((float) ($invoice->total_job_work_amount ?? $invoice->subtotal ?? 0), 2) }}</p>
                    <p>Other Charges: Rs. {{ number_format((float) ($invoice->other_charges ?? 0), 2) }}</p>
                    <p>Discount: - Rs. {{ number_format((float) ($invoice->discount ?? 0), 2) }}</p>
                    <p><strong>Taxable: Rs. {{ number_format((float) ($invoice->taxable_amount ?? $invoice->subtotal ?? 0), 2) }}</strong></p>
                    @if(($invoice->tax_mode ?? 'intra_state') === 'intra_state')
                        <p>CGST: Rs. {{ number_format((float) ($invoice->cgst ?? 0), 2) }}</p>
                        <p>SGST: Rs. {{ number_format((float) ($invoice->sgst ?? 0), 2) }}</p>
                    @else
                        <p>IGST: Rs. {{ number_format((float) ($invoice->igst ?? 0), 2) }}</p>
                    @endif
                    <p>Round off: Rs. {{ number_format((float) ($invoice->round_off ?? 0), 2) }}</p>
                    <p class="grand">Grand Total: Rs. {{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</p>
                </div>
            </td>
        </tr>
    </table>

    <div class="declaration">
        <div class="label">Declaration</div>
        We hereby certify that the particulars given above are true and correct. All goods/services mentioned in this invoice are supplied as per agreed terms.
    </div>

    <div class="sign">
        @if(!empty($invoice->signature_data) && str_starts_with((string) $invoice->signature_data, 'data:image'))
            <img src="{{ $invoice->signature_data }}" alt="Signature">
        @endif
        <p style="border-top:1px solid #1e293b;padding-top:4px;min-width:180px;display:inline-block;">
            {{ $invoice->authorized_person_name ?? '' }}
        </p><br>
        <span style="color:#64748b;">{{ $invoice->designation ?? 'Authorized Signatory' }}</span>
    </div>
</body>
</html>
