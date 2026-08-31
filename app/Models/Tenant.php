<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name',
        'plan',
        'status',
        'api_key',
    ];

   

    public function websites(): HasMany
    {
        return $this->hasMany(
            Website::class
        );
    }

    public function users(): HasMany
    {
        return $this->hasMany(
            User::class
        );
    }

    public function leads(): HasMany
    {
        return $this->hasMany(
            Lead::class
        );
    }

    

    public function aiAgents(): HasMany
    {
        return $this->hasMany(
            AiAgent::class
        );
    }

    public function channelConnections(): HasMany
    {
        return $this->hasMany(
            ChannelConnection::class
        );
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(
            Contact::class
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
}