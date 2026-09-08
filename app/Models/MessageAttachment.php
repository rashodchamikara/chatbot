<?php

namespace App\Models;

<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
<<<<<<< HEAD
=======
    use HasFactory;

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
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
<<<<<<< HEAD
        return $this->belongsTo(
            Message::class
        );
    }
}
=======
        return $this->belongsTo(Message::class);
    }
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
