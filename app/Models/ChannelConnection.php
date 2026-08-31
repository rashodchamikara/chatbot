<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChannelConnection extends Model
{
    use HasFactory;

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
    }

    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(
            AiAgent::class
        );
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(
            Website::class
        );
    }

    public function contactIdentities(): HasMany
    {
        return $this->hasMany(
            ContactIdentity::class
        );
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(
            Conversation::class
        );
    }

    public function messages(): HasMany
    {
        return $this->hasMany(
            Message::class
        );
    }

    public function inboundWebhookEvents(): HasMany
    {
        return $this->hasMany(
            InboundWebhookEvent::class
        );
    }
}