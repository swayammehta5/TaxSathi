<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'message'];
}
