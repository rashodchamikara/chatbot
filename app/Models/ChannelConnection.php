<?php

namespace App\Models;

<<<<<<< HEAD
=======
use App\Enums\ChannelConnectionStatus;
use App\Enums\ChannelType;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
<<<<<<< HEAD
=======
use Illuminate\Support\Str;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

class ChannelConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'ai_agent_id',
        'website_id',
<<<<<<< HEAD

=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        'type',
        'provider',
        'name',
        'status',
<<<<<<< HEAD

        'external_account_id',
        'external_sender_id',

        'webhook_key',

        'credentials',
        'settings',

        'connected_at',
        'last_webhook_at',
        'last_health_check_at',

        'last_error',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',

        'connected_at' => 'datetime',
        'last_webhook_at' => 'datetime',
        'last_health_check_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
=======
        'external_account_id',
        'external_sender_id',
        'webhook_key',
        'credentials',
        'settings',
        'connected_at',
        'last_webhook_at',
        'last_health_check_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'status' => ChannelConnectionStatus::class,
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'connected_at' => 'datetime',
            'last_webhook_at' => 'datetime',
            'last_health_check_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChannelConnection $connection): void {
            if (!$connection->webhook_key) {
                $connection->webhook_key = (string) Str::ulid();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    public function aiAgent(): BelongsTo
    {
<<<<<<< HEAD
        return $this->belongsTo(
            AiAgent::class
        );
=======
        return $this->belongsTo(AiAgent::class);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    public function website(): BelongsTo
    {
<<<<<<< HEAD
        return $this->belongsTo(
            Website::class
        );
=======
        return $this->belongsTo(Website::class);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    public function contactIdentities(): HasMany
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
=======
        return $this->hasMany(Conversation::class);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    public function messages(): HasMany
    {
<<<<<<< HEAD
        return $this->hasMany(
            Message::class
        );
=======
        return $this->hasMany(Message::class);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    }

    public function inboundWebhookEvents(): HasMany
    {
<<<<<<< HEAD
        return $this->hasMany(
            InboundWebhookEvent::class
        );
    }
}
=======
        return $this->hasMany(InboundWebhookEvent::class);
    }
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
