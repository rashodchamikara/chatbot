<?php

namespace App\Models;

<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgent extends Model
{
<<<<<<< HEAD
=======
    use HasFactory;

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    protected $fillable = [
        'tenant_id',
        'name',
        'status',
        'instructions',
        'default_language',
        'model_settings',
        'handover_settings',
        'business_hours',
    ];

    protected function casts(): array
    {
        return [
            'model_settings' => 'array',
            'handover_settings' => 'array',
            'business_hours' => 'array',
        ];
    }

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

    public function channelConnections(): HasMany
    {
<<<<<<< HEAD
        return $this->hasMany(
            ChannelConnection::class
        );
=======
        return $this->hasMany(ChannelConnection::class);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    public function websites(): HasMany
    {
<<<<<<< HEAD
        return $this->hasMany(
            Website::class
        );
=======
        return $this->hasMany(Website::class);
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
