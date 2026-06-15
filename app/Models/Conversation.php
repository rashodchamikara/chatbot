<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use HasFactory;
    protected $fillable = [
        'website_id',
        'visitor_id',
        'status',
        'mode',
        'realtime_token',
        'assigned_agent_id',
        'lead_id',
        'lead_stage',
        'live_requested_at',
        'live_started_at',
        'live_ended_at',
    ];

    protected $casts = [
        'live_requested_at' => 'datetime',
        'live_started_at' => 'datetime',
        'live_ended_at' => 'datetime',
    ];

     public function website()
    {
        return $this->belongsTo(Website::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
     public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }
    protected static function booted(): void
    {
        static::creating(function ($conversation) {
            if (!$conversation->realtime_token) {
                $conversation->realtime_token = Str::random(64);
            }
        });
    }
     
}
