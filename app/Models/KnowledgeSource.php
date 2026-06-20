<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeSource extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'uuid',
        'tenant_id',
        'website_id',
        'uploaded_by',
        'source_type',
        'name',
        'original_name',
        'storage_disk',
        'storage_path',
        'extracted_path',
        'mime_type',
        'extension',
        'size_bytes',
        'checksum_sha256',
        'status',
        'is_enabled',
        'processing_version',
        'active_version',
        'page_count',
        'chunk_count',
        'extracted_characters',
        'embedding_tokens',
        'priority',
        'valid_from',
        'valid_until',
        'external_job_id',
        'processing_error',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'is_enabled'   => 'boolean',
        'metadata'     => 'array',
        'valid_from'   => 'datetime',
        'valid_until'  => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    public function uploader()
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }

    public function chunks()
    {
        return $this->hasMany(
            KnowledgeChunk::class,
            'knowledge_source_id'
        );
    }

    public function activeChunks()
    {
        return $this->chunks()
            ->where('is_active', true)
            ->where(
                'processing_version',
                $this->active_version
            );
    }

    public function scopeReady($query)
    {
        return $query
            ->where('status', 'ready')
            ->where('is_enabled', true);
    }
    
}
