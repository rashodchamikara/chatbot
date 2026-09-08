<?php

namespace App\Models;

<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
<<<<<<< HEAD
=======
    use HasFactory;

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'company',
        'status',
        'metadata',
    ];

    protected $casts = [
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

    public function identities(): HasMany
    {
<<<<<<< HEAD
        return $this->hasMany(
            ContactIdentity::class
        );
=======
        return $this->hasMany(ContactIdentity::class);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    public function conversations(): HasMany
    {
<<<<<<< HEAD
        return $this->hasMany(
            Conversation::class
        );
    }
}
=======
        return $this->hasMany(Conversation::class);
    }
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
