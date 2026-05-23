<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Invoice extends Model
{
    protected $fillable = [
        'firm_id',
        'customer_name',
        'company_name',
        'billing_address',
        'shipping_address',
        'contact_number',
        'email',
        'gstin',
        'state',
        'state_code',
        'customer_address',
        'gst_number',
        'invoice_number',
        'date',
        'due_date',
        'po_number',
        'delivery_challan_number',
        'payment_terms',
        'place_of_supply',
        'direction',
        'tax_mode',
        'total_job_work_amount',
        'other_charges',
        'discount',
        'taxable_amount',
        'subtotal',
        'cgst_percent',
        'sgst_percent',
        'igst_percent',
        'cgst',
        'sgst',
        'igst',
        'round_off',
        'total_amount',
        'account_holder_name',
        'bank_name',
        'branch_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        'authorized_person_name',
        'designation',
        'signature_data',
        'pdf_path',
    ];

    protected static function booted(): void
    {
        static::saved(static function () {
            Cache::forget('api.reports.dashboard');
            Cache::forget('api.dashboard.summary');
        });
        static::deleted(static function () {
            Cache::forget('api.reports.dashboard');
            Cache::forget('api.dashboard.summary');
        });
    }

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function scopeForMonth($query, int $month, int $year)
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = sprintf('%04d-%02d-%02d', $year, $month, (int) date('t', strtotime($start)));

        return $query->where('date', '>=', $start)->where('date', '<=', $end);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'float',
            'total_job_work_amount' => 'float',
            'other_charges' => 'float',
            'discount' => 'float',
            'taxable_amount' => 'float',
            'cgst' => 'float',
            'sgst' => 'float',
            'igst' => 'float',
            'total_amount' => 'float',
            'cgst_percent' => 'float',
            'sgst_percent' => 'float',
            'igst_percent' => 'float',
            'round_off' => 'float',
        ];
    }
}
