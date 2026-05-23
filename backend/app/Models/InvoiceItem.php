<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'pl',
        'th',
        'hrs_set',
        'total_hrs',
        'hsn',
        'hsn_sac_code',
        'quantity',
        'rate',
        'amount',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    protected function casts(): array
    {
        return [
            'pl' => 'float',
            'th' => 'float',
            'hrs_set' => 'float',
            'total_hrs' => 'float',
            'rate' => 'float',
            'amount' => 'float',
        ];
    }
}
