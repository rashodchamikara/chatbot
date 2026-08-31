<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        /*
         * Existing website-chat fields.
         */
        'conversation_id',
        'user_id',
        'sender',
        'role',
        'message',
        'tokens_used',
        'is_system',

        /*
         * Omnichannel fields.
         */
        'channel_connection_id',
        'sender_user_id',

        'external_message_id',
        'external_reply_to_id',

        'direction',
        'sender_type',
        'message_type',

        'payload',

        'status',

        'provider_status',
        'error_code',
        'error_message',

        'is_ai_generated',

        'prompt_tokens',
        'completion_tokens',

        'provider_created_at',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'is_system' => 'boolean',

        'payload' => 'array',

        'is_ai_generated' => 'boolean',

        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',

        'provider_created_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(
            Conversation::class
        );
    }

    /**
     * Existing website live-chat user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    /**
     * Omnichannel sender.
     */
    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'sender_user_id'
        );
    }

    public function channelConnection(): BelongsTo
    {
        return $this->belongsTo(
            ChannelConnection::class
        );
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(
            MessageAttachment::class
        );
    }
}