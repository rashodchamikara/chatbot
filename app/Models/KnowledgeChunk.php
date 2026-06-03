<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_page_id',
        'website_id',
        'chunk_text',
        'embedding',
        'chunk_index',
    ];

     protected $casts = [
        'embedding' => 'array',
    ];
    public function page()
    {
        return $this->belongsTo(KnowledgePage::class, 'knowledge_page_id');
    }

    public function website()
    {
        return $this->belongsTo(Website::class);
    }
}
