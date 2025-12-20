<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectMessageAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'direct_message_id',
        'filename',
        'original_name',
        'mime_type',
        'file_size',
        'file_path',
        'file_url',
        'metadata'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array'
    ];

    public function directMessage()
    {
        return $this->belongsTo(DirectMessage::class);
    }

    public function isImage()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo()
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function isAudio()
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    public function isDocument()
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ]);
    }

    public function getFileSizeFormatted()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}