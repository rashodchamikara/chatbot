<?php

namespace App\Models;

use App\Enums\ChannelType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Relations\HasOne;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Website extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'ai_agent_id',
<<<<<<< HEAD

=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
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
<<<<<<< HEAD
=======
    {
        return $this->belongsTo(AiAgent::class);
    }

    public function channelConnections(): HasMany
    {
        return $this->hasMany(ChannelConnection::class);
    }

    public function websiteChannelConnection(): HasOne
    {
        return $this->hasOne(ChannelConnection::class)
            ->where('type', ChannelType::Website->value);
    }

    public function knowledgePages(): HasMany
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    {
        return $this->belongsTo(
            AiAgent::class
        );
    }

<<<<<<< HEAD
    public function knowledgePages(): HasMany
=======
    public function knowledgeChunks(): HasMany
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    {
        return $this->hasMany(
            KnowledgePage::class
        );
    }

<<<<<<< HEAD
    public function knowledgeChunks(): HasMany
=======
    public function conversations(): HasMany
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    {
        return $this->hasMany(
            KnowledgeChunk::class
        );
    }

<<<<<<< HEAD
    public function conversations(): HasMany
=======
    public function leads(): HasMany
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    {
        return $this->hasMany(
            Conversation::class
        );
    }

<<<<<<< HEAD
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
=======
    public function knowledgeSources(): HasMany
    {
        return $this->hasMany(KnowledgeSource::class);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    public function suspendedBy(): BelongsTo
    {
<<<<<<< HEAD
        return $this->belongsTo(
            User::class,
            'suspended_by'
        );
=======
        return $this->belongsTo(User::class, 'suspended_by');
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    protected static function booted(): void
    {
<<<<<<< HEAD
        static::creating(
            function (
                Website $website
            ): void {
                if (!$website->realtime_token) {
                    $website->realtime_token =
                        Str::random(64);
                }
=======
        static::creating(function (Website $website): void {
            if (!$website->realtime_token) {
                $website->realtime_token = Str::random(64);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            }
        );
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
