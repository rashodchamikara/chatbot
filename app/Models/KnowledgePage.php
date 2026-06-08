<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgePage extends Model
{
    use HasFactory;

    protected $fillable = [
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
    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    public function chunks()
    {
        return $this->hasMany(KnowledgeChunk::class);
    }
}
