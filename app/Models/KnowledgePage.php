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
        'content',
        'content_hash',
        'is_indexed',
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
