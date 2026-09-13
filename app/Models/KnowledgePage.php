<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgePage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'ai_agent_id',
        'website_id',
        'url',
        'title',
        'type',
        'source_type',
        'content',
        'content_hash',
        'is_indexed',
        'is_active',
        'indexed_at',
    ];

    protected $casts = [
        'is_indexed' => 'boolean',
        'is_active' => 'boolean',
        'indexed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'knowledge_page_id');
    }
}
