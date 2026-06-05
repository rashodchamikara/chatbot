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
        'status',
        'extra_data',
        'qualified_at',
        'contacted_at',
    ];

    protected $casts = [
        'extra_data' => 'array',
        'qualified_at' => 'datetime',
        'contacted_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}