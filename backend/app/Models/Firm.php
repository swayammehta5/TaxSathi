<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Firm extends Model
{
    protected $fillable = [
        'name',
        'gstin',
        'address',
        'state',
        'state_code',
        'contact_number',
        'email',
        'phone',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
