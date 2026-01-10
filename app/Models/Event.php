<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScope;

class Event extends Model
{
    use HasFactory, SoftDeletes, TenantScope;

    protected $fillable = [
        'merchant_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'type',
        'color',
        'user_id',
        'status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 