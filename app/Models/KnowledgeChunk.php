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
        'metadata'           => 'array',
        'is_active'          => 'boolean',
        'embedded_at'        => 'datetime',
        'processing_version' => 'integer',
        'token_count'        => 'integer',
        'page_number'        => 'integer',
        'chunk_index' => 'integer',
    ];

    public function page()
    {
        return $this->belongsTo(KnowledgePage::class, 'knowledge_page_id');
    }

    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    public function knowledgePage()
    {
        return $this->belongsTo(
            KnowledgePage::class,
            'knowledge_page_id'
        );
    }

    public function knowledgeSource()
    {
        return $this->belongsTo(
            KnowledgeSource::class,
            'knowledge_source_id'
        );
    }
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForWebsite($query, int $websiteId)
    {
        return $query->where(
            'website_id',
            $websiteId
        );
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
            return $this->knowledgeSource->name;
        }

        if ($this->knowledgePage) {
            return $this->knowledgePage->title
                ?? $this->knowledgePage->url
                ?? 'Website page';
        }

        return 'Unknown source';
    }
}
