<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
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
    ];

    protected $casts = [
        'verify_domain' => 'boolean',
        'is_active' => 'boolean',
        'indexing_started_at' => 'datetime',
        'indexing_completed_at' => 'datetime',
        'live_chat_enabled' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function knowledgePages()
    {
        return $this->hasMany(KnowledgePage::class);
    }

    public function knowledgeChunks()
    {
        return $this->hasMany(KnowledgeChunk::class);
    }
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
    protected static function booted(): void
    {
        static::creating(function ($website) {
            if (!$website->realtime_token) {
                $website->realtime_token = Str::random(64);
            }
        });
    }
}
