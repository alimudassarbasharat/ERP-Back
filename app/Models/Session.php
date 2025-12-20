<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Session extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
        'merchant_id'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    /**
     * Get the students associated with the session.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get the classes associated with the session.
     */
    public function classes()
    {
        return $this->hasMany(Classes::class);
    }

    /**
     * Get the user who created the session.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the session.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}