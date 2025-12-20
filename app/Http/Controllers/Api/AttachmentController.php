<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageAttachment;
use App\Models\DirectMessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AttachmentController extends Controller
{
    /**
     * Download a message attachment
     */
    public function downloadMessageAttachment($id)
    {
        $attachment = MessageAttachment::findOrFail($id);
        $user = Auth::user();
        
        // Check if user has access to this attachment
        $message = $attachment->message;
        if ($message && !$message->channel->isUserMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this file'
            ], 403);
        }
        
        $filePath = $attachment->file_path;
        
        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }
        
        return Storage::disk('public')->download($filePath, $attachment->original_name);
    }
    
    /**
     * Download a direct message attachment
     */
    public function downloadDirectMessageAttachment($id)
    {
        $attachment = DirectMessageAttachment::findOrFail($id);
        $user = Auth::user();
        
        // Check if user has access to this attachment
        $directMessage = $attachment->directMessage;
        if ($directMessage && !$directMessage->conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this file'
            ], 403);
        }
        
        $filePath = $attachment->file_path;
        
        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }
        
        return Storage::disk('public')->download($filePath, $attachment->original_name);
    }
    
    /**
     * Get attachment preview (for images)
     */
    public function preview($type, $id)
    {
        if ($type === 'message') {
            $attachment = MessageAttachment::findOrFail($id);
            $user = Auth::user();
            
            // Check access
            $message = $attachment->message;
            if ($message && !$message->channel->isUserMember($user)) {
                abort(403);
            }
        } else {
            $attachment = DirectMessageAttachment::findOrFail($id);
            $user = Auth::user();
            
            // Check access
            $directMessage = $attachment->directMessage;
            if ($directMessage && !$directMessage->conversation->participants()->where('user_id', $user->id)->exists()) {
                abort(403);
            }
        }
        
        // Only allow preview for images
        if (!$attachment->isImage()) {
            abort(404);
        }
        
        $filePath = $attachment->file_path;
        
        if (!Storage::disk('public')->exists($filePath)) {
            abort(404);
        }
        
        $file = Storage::disk('public')->get($filePath);
        $mimeType = $attachment->mime_type;
        
        return response($file, 200)->header('Content-Type', $mimeType);
    }
}