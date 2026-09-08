<?php

namespace App\Models;

<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactIdentity extends Model
{
<<<<<<< HEAD
=======
    use HasFactory;

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    protected $fillable = [
        'tenant_id',
        'contact_id',
        'channel_connection_id',
        'channel',
        'external_user_id',
        'display_name',
        'username',
        'normalized_address',
        'is_verified',
        'opted_out_at',
        'metadata',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'opted_out_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
<<<<<<< HEAD
        return $this->belongsTo(
            Tenant::class
        );
=======
        return $this->belongsTo(Tenant::class);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    public function contact(): BelongsTo
    {
<<<<<<< HEAD
        return $this->belongsTo(
            Contact::class
        );
=======
        return $this->belongsTo(Contact::class);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    public function channelConnection(): BelongsTo
    {
<<<<<<< HEAD
        return $this->belongsTo(
            ChannelConnection::class
        );
    }
}
=======
        return $this->belongsTo(ChannelConnection::class);
    }
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
