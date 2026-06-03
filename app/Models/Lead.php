<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'website_id',
        'conversation_id',
        'name',
        'email',
        'phone',
        'country',
        'preferred_contact_time',
        'product_interest',
        'lead_score',
        'status'
    ];
}