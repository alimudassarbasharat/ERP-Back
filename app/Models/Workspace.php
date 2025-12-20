<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'name',
        'slug',
        'icon',
        'color',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get all tickets in this workspace
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get active tickets count
     */
    public function getActiveTicketsCountAttribute()
    {
        return $this->tickets()->whereNotIn('status', ['complete'])->count();
    }

    /**
     * Get completed tickets count
     */
    public function getCompletedTicketsCountAttribute()
    {
        return $this->tickets()->where('status', 'complete')->count();
    }

    /**
     * Scope to filter by merchant
     */
    public function scopeForMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    /**
     * Scope to get active workspaces
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
