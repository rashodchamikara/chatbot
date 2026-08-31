<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        /*
         * Existing website live-chat fields.
         */
        'website_id',
        'visitor_id',
        'status',
        'mode',
        'realtime_token',

        'assigned_agent_id',

        'lead_id',
        'lead_stage',

        'live_requested_at',
        'live_started_at',
        'live_ended_at',

        /*
         * Omnichannel fields.
         */
        'tenant_id',
        'ai_agent_id',
        'channel_connection_id',
        'contact_id',

        'assigned_user_id',

        'external_thread_id',

        'subject',
        'priority',

        'unread_count',

        'first_response_at',
        'last_message_at',
        'last_inbound_at',
        'reply_window_expires_at',

        'metadata',
    ];

    protected $casts = [
        /*
         * Existing live-chat dates.
         */
        'live_requested_at' => 'datetime',
        'live_started_at' => 'datetime',
        'live_ended_at' => 'datetime',

        /*
         * Omnichannel dates.
         */
        'first_response_at' => 'datetime',
        'last_message_at' => 'datetime',
        'last_inbound_at' => 'datetime',
        'reply_window_expires_at' => 'datetime',

        'unread_count' => 'integer',

        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Existing website relationships
    |--------------------------------------------------------------------------
    */

    public function website(): BelongsTo
    {
        return $this->belongsTo(
            Website::class
        );
    }

    public function messages(): HasMany
    {
        return $this->hasMany(
            Message::class
        );
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(
            Lead::class
        );
    }

    /**
     * Existing website/live-chat agent assignment.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_agent_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Omnichannel relationships
    |--------------------------------------------------------------------------
    */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(
            AiAgent::class
        );
    }

    public function channelConnection(): BelongsTo
    {
        return $this->belongsTo(
            ChannelConnection::class
        );
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(
            Contact::class
        );
    }

    /**
     * New omnichannel assignment field.
     *
     * Kept separately from assigned_agent_id so
     * the existing website live-chat feature
     * continues working unchanged.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_user_id'
        );
    }

    protected static function booted(): void
    {
        static::creating(
            function (
                Conversation $conversation
            ): void {
                if (!$conversation->realtime_token) {
                    $conversation->realtime_token =
                        Str::random(64);
                }
            }
        );
    }
}