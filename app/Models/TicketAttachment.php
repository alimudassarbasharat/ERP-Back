<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'ticket_id',
        'user_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['file_url', 'formatted_size'];

    /**
     * Boot method to log activity
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($attachment) {
            $attachment->ticket->logActivity(
                'attachment_added',
                auth()->user()->name . ' uploaded ' . $attachment->file_name,
                ['attachment_id' => $attachment->id]
            );
        });
    }

    /**
     * Relationships
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get file URL with full backend URL
     */
    public function getFileUrlAttribute()
    {
        // Get the storage URL (relative path like /storage/...)
        $relativePath = Storage::url($this->file_path);
        
        // Prepend backend URL from config
        $backendUrl = config('app.url');
        
        // Return full URL
        return $backendUrl . $relativePath;
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
