<?php

namespace App\Models;

use App\Enums\ChannelConnectionStatus;
use App\Enums\ChannelType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ChannelConnection extends Model
{
    protected $fillable = [
        'tenant_id',
        'ai_agent_id',
        'website_id',
        'type',
        'provider',
        'name',
        'status',
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
    }

    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}