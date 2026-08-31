<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Website extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'ai_agent_id',

        'name',

        'chatbot_name',
        'chatbot_theme',
        'chatbot_avatar',
        'chatbot_instructions',

        'domain',
        'verify_domain',

        'embed_token',

        'is_active',

        'indexing_status',
        'indexing_started_at',
        'indexing_completed_at',
        'indexing_error',

        'realtime_token',

        'live_chat_enabled',

        'suspended_at',
        'suspended_by',
    ];

    protected $casts = [
        'verify_domain' => 'boolean',
        'is_active' => 'boolean',

        'indexing_started_at' => 'datetime',
        'indexing_completed_at' => 'datetime',

        'live_chat_enabled' => 'boolean',

        'suspended_at' => 'datetime',
    ];

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

    public function knowledgePages(): HasMany
    {
        return $this->hasMany(
            KnowledgePage::class
        );
    }

    public function knowledgeChunks(): HasMany
    {
        return $this->hasMany(
            KnowledgeChunk::class
        );
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(
            Conversation::class
        );
    }

    public function leads(): HasMany
    {
        return $this->hasMany(
            Lead::class
        );
    }

    public function knowledgeSources(): HasMany
    {
        return $this->hasMany(
            KnowledgeSource::class
        );
    }

    public function channelConnections(): HasMany
    {
        return $this->hasMany(
            ChannelConnection::class
        );
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'suspended_by'
        );
    }

    protected static function booted(): void
    {
        static::creating(
            function (
                Website $website
            ): void {
                if (!$website->realtime_token) {
                    $website->realtime_token =
                        Str::random(64);
                }
            }
        );
    }
}