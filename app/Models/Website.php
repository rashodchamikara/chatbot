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
        'domain',
        'verify_domain',
        'embed_token',
        'is_active'
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
}
