<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'website_id',
        'type',
        'name',
        'status',
        'external_id',
        'credentials',
        'settings',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
    ];
}