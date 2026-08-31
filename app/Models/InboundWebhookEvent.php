<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundWebhookEvent extends Model
{
    protected $fillable = [
        'channel_connection_id',
        'provider',
        'event_type',
        'external_event_id',
        'payload_hash',
        'payload',
        'headers',
        'status',
        'attempts',
        'received_at',
        'processing_started_at',
        'processed_at',
        'failed_at',
        'last_error',
        'metadata',
    ];

    protected $casts = [
        'headers' => 'array',
        'metadata' => 'array',

        'attempts' => 'integer',

        'received_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function channelConnection(): BelongsTo
    {
        return $this->belongsTo(
            ChannelConnection::class
        );
    }
}