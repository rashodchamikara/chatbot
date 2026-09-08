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
        /*
        |--------------------------------------------------------------------------
        | Webhook data
        |--------------------------------------------------------------------------
        |
        | Provider webhook bodies are stored as JSON in the database.
        |
        | Casting payload to array is especially important for WhatsApp/
        | Meta webhooks because their payloads contain deeply nested
        | structures such as:
        |
        | entry
        |   -> changes
        |       -> value
        |           -> metadata
        |           -> contacts
        |           -> messages
        |           -> statuses
        |
        */

        'payload' => 'array',

        /*
         * HTTP/request headers captured with the webhook.
         */
        'headers' => 'array',

        /*
         * Internal application metadata associated with
         * processing the webhook.
         */
        'metadata' => 'array',

        /*
        |--------------------------------------------------------------------------
        | Processing state
        |--------------------------------------------------------------------------
        */

        'attempts' => 'integer',

        /*
        |--------------------------------------------------------------------------
        | Processing timestamps
        |--------------------------------------------------------------------------
        */

        'received_at' => 'datetime',

        'processing_started_at' => 'datetime',

        'processed_at' => 'datetime',

        'failed_at' => 'datetime',
    ];

    /**
     * Channel connection that received this webhook.
     */
    public function channelConnection(): BelongsTo
    {
        return $this->belongsTo(
            ChannelConnection::class
        );
    }
}