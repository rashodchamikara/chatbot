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

<<<<<<< HEAD
   

=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
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

<<<<<<< HEAD
    

    public function aiAgents(): HasMany
    {
        return $this->hasMany(
            AiAgent::class
        );
=======
    public function aiAgents(): HasMany
    {
        return $this->hasMany(AiAgent::class);
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

    public function contacts(): HasMany
    {
<<<<<<< HEAD
        return $this->hasMany(
            Contact::class
        );
    }

    public function contactIdentities(): HasMany
    {
        return $this->hasMany(
            ContactIdentity::class
        );
=======
        return $this->hasMany(Contact::class);
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
