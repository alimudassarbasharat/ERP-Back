<?php

namespace App\Models;

use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    use TenantScope;

    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'merchant_id'
    ];

    protected $hidden = [
        'auth_token'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
