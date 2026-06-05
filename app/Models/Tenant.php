<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name',
        'plan',
        'status',
        'api_key'
    ];

    public function websites()
    {
        return $this->hasMany(Website::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
}
