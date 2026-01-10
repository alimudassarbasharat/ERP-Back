<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
    const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];
    const ALLOWED_AUDIO_TYPES = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/webm'];
    const ALLOWED_DOCUMENT_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain'
    ];

    public function uploadFile(UploadedFile $file, string $folder = 'direct-messages'): array
    {
        $this->validateFile($file);

        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        $extension = $file->getClientOriginalExtension();
        
        $filename = Str::uuid() . '.' . $extension;
        $path = $file->storeAs($folder . '/' . date('Y/m'), $filename, 'public');

        return [
            'filename' => $filename,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'file_path' => $path,
            'file_url' => Storage::url($path),
            'type' => $this->getFileType($mimeType),
            'metadata' => [
                'extension' => $extension,
                'uploaded_at' => now()->toIso8601String()
            ]
        ];
    }

    public function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds maximum allowed size of 50MB');
        }

        $mimeType = $file->getMimeType();
        $allowedTypes = array_merge(
            self::ALLOWED_IMAGE_TYPES,
            self::ALLOWED_VIDEO_TYPES,
            self::ALLOWED_AUDIO_TYPES,
            self::ALLOWED_DOCUMENT_TYPES
        );

        if (!in_array($mimeType, $allowedTypes)) {
            throw new \Exception('File type not allowed');
        }
    }

    public function getFileType(string $mimeType): string
    {
        if (in_array($mimeType, self::ALLOWED_IMAGE_TYPES)) {
            return 'image';
        }
        if (in_array($mimeType, self::ALLOWED_VIDEO_TYPES)) {
            return 'video';
        }
        if (in_array($mimeType, self::ALLOWED_AUDIO_TYPES)) {
            return 'audio';
        }
        if (in_array($mimeType, self::ALLOWED_DOCUMENT_TYPES)) {
            return 'document';
        }
        return 'file';
    }

    public function deleteFile(string $filePath): bool
    {
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->delete($filePath);
        }
        return false;
    }
}
