<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgent extends Model
{
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
        return $this->belongsTo(Tenant::class);
    }

    public function channelConnections(): HasMany
    {
        return $this->hasMany(ChannelConnection::class);
    }

    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }
}
