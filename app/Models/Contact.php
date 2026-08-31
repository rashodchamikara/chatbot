<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
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
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function identities(): HasMany
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
}