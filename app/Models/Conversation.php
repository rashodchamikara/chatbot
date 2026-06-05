<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;
    protected $fillable = [
        'website_id',
        'visitor_id',
        'status',
        'lead_id',
        'lead_stage',
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
     
}
