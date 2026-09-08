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

<<<<<<< HEAD
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

=======
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
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        'provider_created_at',
        'sent_at',
        'delivered_at',
        'read_at',
<<<<<<< HEAD
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

=======
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'payload' => 'array',
        'is_ai_generated' => 'boolean',
        'tokens_used' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'provider_created_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(
            Conversation::class
        );
    }

<<<<<<< HEAD
    /**
     * Existing website live-chat user.
     */
=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

<<<<<<< HEAD
    /**
     * Omnichannel sender.
     */
    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'sender_user_id'
        );
=======
    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    public function channelConnection(): BelongsTo
    {
<<<<<<< HEAD
        return $this->belongsTo(
            ChannelConnection::class
        );
=======
        return $this->belongsTo(ChannelConnection::class);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    public function attachments(): HasMany
    {
<<<<<<< HEAD
        return $this->hasMany(
            MessageAttachment::class
        );
    }
}
=======
        return $this->hasMany(MessageAttachment::class);
    }
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
