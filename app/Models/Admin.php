<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
class Admin extends Authenticatable
{
    use HasFactory, HasApiTokens, HasRoles, SoftDeletes;

    protected $guarded = [];
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'role_id',
        'status',
        'merchant_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getRoleNamesAttribute()
    {
        return $this->roles->pluck('name');
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Admin::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Admin::class, 'parent_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Admin::class, 'parent_id')
            ->with('subordinates');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
