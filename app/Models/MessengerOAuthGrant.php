<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MessengerOAuthGrant extends Model
{
    protected $table = 'messenger_oauth_grants';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $hidden = ['pages', 'state_hash', 'session_hash'];
    protected function casts(): array { return ['pages' => 'encrypted:array', 'expires_at' => 'datetime']; }
}
