<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TenantScope;

class MentionNotification extends Model
{
    use HasFactory, SoftDeletes, TenantScope;

    protected $fillable = [
        'merchant_id',
        'user_id',
        'message_id',
        'message_type',
        'conversation_id',
        'conversation_type',
        'conversation_name',
        'mentioner_id',
        'message_preview',
        'is_read',
        'read_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioner_id');
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }
}
