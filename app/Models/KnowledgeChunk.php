<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_page_id',
        'knowledge_source_id',
        'website_id',
        'chunk_text',
        'embedding',
        'chunk_index',
        'processing_version',
        'token_count',
        'page_number',
        'section_title',
        'content_hash',
        'metadata',
        'is_active',
        'embedded_at',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'embedded_at' => 'datetime',
        'processing_version' => 'integer',
        'token_count' => 'integer',
        'page_number' => 'integer',
        'chunk_index' => 'integer',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(KnowledgePage::class, 'knowledge_page_id');
    }

    public function knowledgePage(): BelongsTo
    {
        return $this->belongsTo(KnowledgePage::class, 'knowledge_page_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(KnowledgeSource::class, 'knowledge_source_id');
    }

    public function knowledgeSource(): BelongsTo
    {
        return $this->belongsTo(KnowledgeSource::class, 'knowledge_source_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForWebsite($query, int $websiteId)
    {
        return $query->where('website_id', $websiteId);
    }

    public function scopeWithEmbedding($query)
    {
        return $query->whereNotNull('embedding');
    }

    public function scopeWithAnyKnowledgeSource($query)
    {
        return $query->where(function ($query) {
            $query->whereNotNull('knowledge_page_id')
                ->orWhereNotNull('knowledge_source_id');
        });
    }

    public function isCrawledPageChunk(): bool
    {
        return $this->knowledge_page_id !== null;
    }

    public function isUploadedSourceChunk(): bool
    {
        return $this->knowledge_source_id !== null;
    }

    public function getSourceNameAttribute(): string
    {
        if ($this->knowledgeSource) {
            return $this->knowledgeSource->original_name
                ?? $this->knowledgeSource->name
                ?? 'Uploaded knowledge source';
        }

        if ($this->knowledgePage) {
            return $this->knowledgePage->title
                ?? $this->knowledgePage->url
                ?? 'Website page';
        }

        return 'Unknown source';
    }

    public function getSourceUrlAttribute(): ?string
    {
        return $this->knowledgePage?->url
            ?? $this->page?->url
            ?? null;
    }

    public function getSourceTypeAttribute(): string
    {
        if ($this->knowledge_source_id !== null) {
            return 'uploaded_file';
        }

        if ($this->knowledge_page_id !== null) {
            return 'web_page';
        }

        return 'unknown';
    }
}