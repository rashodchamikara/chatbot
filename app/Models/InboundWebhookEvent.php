<?php

namespace App\Models;

<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundWebhookEvent extends Model
{
<<<<<<< HEAD
=======
    use HasFactory;

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
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
<<<<<<< HEAD
        'headers' => 'array',
        'metadata' => 'array',

        'attempts' => 'integer',

=======
        'payload' => 'array',
        'headers' => 'array',
        'metadata' => 'array',
        'attempts' => 'integer',
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        'received_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function channelConnection(): BelongsTo
    {
<<<<<<< HEAD
        return $this->belongsTo(
            ChannelConnection::class
        );
    }
}
=======
        return $this->belongsTo(ChannelConnection::class);
    }
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
