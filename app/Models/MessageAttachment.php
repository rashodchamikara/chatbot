<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $fillable = [
        'message_id',
        'external_attachment_id',
        'type',
        'mime_type',
        'original_name',
        'storage_disk',
        'storage_path',
        'external_url',
        'size',
        'checksum',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'metadata' => 'array',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(
            Message::class
        );
    }
}