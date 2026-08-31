<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactIdentity extends Model
{
    protected $fillable = [
        'tenant_id',
        'contact_id',
        'channel_connection_id',
        'channel',
        'external_user_id',
        'display_name',
        'username',
        'normalized_address',
        'is_verified',
        'opted_out_at',
        'metadata',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'opted_out_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(
            Contact::class
        );
    }

    public function channelConnection(): BelongsTo
    {
        return $this->belongsTo(
            ChannelConnection::class
        );
    }
}