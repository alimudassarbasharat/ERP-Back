<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketSubtask;
use App\Models\TicketComment;
use App\Models\TicketTimeLog;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    /**
     * Get merchant ID from authenticated user
     */
    private function getMerchantId()
    {
        return auth()->user()->merchant_id ?? 1;
    }

    /**
     * Display a listing of tickets
     */
    public function index(Request $request)
    {
        try {
            $merchantId = $this->getMerchantId();

            $query = Ticket::forMerchant($merchantId)
                ->with([
                    'workspace',
                    'assignee:id,name,email',
                    'reporter:id,name,email',
                    'subtasks'
                ])
                ->withCount(['comments', 'attachments']);

            // Filter by workspace
            if ($request->has('workspace_id') && $request->workspace_id !== 'all') {
                $query->where('workspace_id', $request->workspace_id);
            }

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by priority
            if ($request->has('priority') && $request->priority !== 'all') {
                $query->where('priority', $request->priority);
            }

            // Filter by assignee
            if ($request->has('assignee_id')) {
                $query->where('assignee_id', $request->assignee_id);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('ticket_number', 'like', "%{$search}%");
                });
    }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $tickets = $query->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $tickets
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tickets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created ticket
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'workspace_id' => 'required|exists:workspaces,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'category' => 'nullable|string',
                'assignee_id' => 'nullable|exists:users,id'
            ]);

            $merchantId = $this->getMerchantId();

            $ticket = Ticket::create([
                'merchant_id' => $merchantId,
                'workspace_id' => $validated['workspace_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => 'todo',
                'priority' => $validated['priority'],
                'category' => $validated['category'] ?? null,
                'reporter_id' => auth()->id(),
                'assignee_id' => $validated['assignee_id'] ?? null
            ]);

            // Log creation activity
            $ticket->logActivity('created', auth()->user()->name . ' created this ticket', [
                'priority' => $ticket->priority,
                'category' => $ticket->category
            ]);

            // If assigned, log assignment
            if ($ticket->assignee_id) {
                $ticket->logActivity('assignee_changed', auth()->user()->name . ' assigned to ' . $ticket->assignee->name);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ticket created successfully',
                'data' => $ticket->load(['workspace', 'assignee:id,name,email', 'reporter:id,name,email'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified ticket
     */
    public function show($id)
    {
        try {
            $merchantId = $this->getMerchantId();

            // Check if ID is numeric or ticket number (TKT-001)
            $query = Ticket::forMerchant($merchantId)
                ->with([
                    'workspace',
                    'assignee:id,name,email',
                    'reporter:id,name,email',
                    'subtasks.completedByUser',
                    'activities.user:id,name,email',
                    'comments.user:id,name,email',
                    'comments.attachments.user:id,name,email',
                    'comments.voiceRecordings',
                    'comments.replies.user:id,name,email',
                    'comments.replies.attachments.user:id,name,email',
                    'comments.replies.voiceRecordings',
                    'attachments.user:id,name,email',
                    'timeLogs.user:id,name,email'
                ]);

            // If ID starts with TKT-, search by ticket_number
            if (is_string($id) && str_starts_with($id, 'TKT-')) {
                $ticket = $query->where('ticket_number', $id)->firstOrFail();
            } else {
                $ticket = $query->findOrFail($id);
            }

            return response()->json([
                'success' => true,
                'data' => $ticket
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified ticket
     */
    public function update(Request $request, $id)
    {
        try {
            $merchantId = $this->getMerchantId();

            $ticket = Ticket::forMerchant($merchantId)->findOrFail($id);

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'status' => 'sometimes|in:backlog,todo,in-progress,review,complete',
                'priority' => 'sometimes|in:low,medium,high,urgent',
                'category' => 'sometimes|string',
                'assignee_id' => 'nullable|exists:users,id',
                'workspace_id' => 'sometimes|exists:workspaces,id'
            ]);

            // Track changes for activity log
            $changes = [];

            if (isset($validated['status']) && $ticket->status !== $validated['status']) {
                $changes['status'] = [
                    'old' => $ticket->status,
                    'new' => $validated['status']
                ];
                $ticket->logActivity('status_changed', auth()->user()->name . ' changed status from ' . ucfirst($ticket->status) . ' to ' . ucfirst($validated['status']));
                
                if ($validated['status'] === 'complete') {
                    $validated['completed_at'] = now();
                }
            }

            if (isset($validated['priority']) && $ticket->priority !== $validated['priority']) {
                $changes['priority'] = [
                    'old' => $ticket->priority,
                    'new' => $validated['priority']
                ];
                $ticket->logActivity('priority_changed', auth()->user()->name . ' changed priority from ' . ucfirst($ticket->priority) . ' to ' . ucfirst($validated['priority']));
            }

            if (isset($validated['assignee_id']) && $ticket->assignee_id !== $validated['assignee_id']) {
                $oldAssignee = $ticket->assignee ? $ticket->assignee->name : 'Unassigned';
                $newAssignee = $validated['assignee_id'] ? \App\Models\User::find($validated['assignee_id'])->name : 'Unassigned';
                
                $ticket->logActivity('assignee_changed', auth()->user()->name . ' changed assignee from ' . $oldAssignee . ' to ' . $newAssignee);
            }

            if (isset($validated['title']) && $ticket->title !== $validated['title']) {
                $ticket->logActivity('title_updated', auth()->user()->name . ' updated the title');
            }

            if (isset($validated['description']) && $ticket->description !== $validated['description']) {
                $ticket->logActivity('description_updated', auth()->user()->name . ' updated the description');
            }

            $ticket->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Ticket updated successfully',
                'data' => $ticket->load(['workspace', 'assignee:id,name,email', 'reporter:id,name,email'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified ticket
     */
    public function destroy($id)
    {
        try {
            $merchantId = $this->getMerchantId();

            $ticket = Ticket::forMerchant($merchantId)->findOrFail($id);
            $ticket->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ticket deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add subtask to ticket
     */
    public function addSubtask(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255'
            ]);

            $merchantId = $this->getMerchantId();
            $ticket = Ticket::forMerchant($merchantId)->findOrFail($id);

            $position = $ticket->subtasks()->max('position') + 1;

            $subtask = $ticket->subtasks()->create([
                'merchant_id' => $merchantId,
                'title' => $validated['title'],
                'position' => $position,
                'completed' => false
            ]);

            $ticket->logActivity('subtask_added', auth()->user()->name . ' added subtask: ' . $subtask->title);

            return response()->json([
                'success' => true,
                'message' => 'Subtask added successfully',
                'data' => $subtask
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add subtask',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle subtask completion
     */
    public function toggleSubtask($ticketId, $subtaskId)
    {
        try {
            $merchantId = $this->getMerchantId();
            
            $ticket = Ticket::forMerchant($merchantId)->findOrFail($ticketId);
            $subtask = $ticket->subtasks()->findOrFail($subtaskId);

            if ($subtask->completed) {
                $subtask->markAsIncomplete();
            } else {
                $subtask->markAsCompleted();
            }

            return response()->json([
                'success' => true,
                'message' => 'Subtask updated successfully',
                'data' => $subtask
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle subtask',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete subtask
     */
    public function deleteSubtask($ticketId, $subtaskId)
    {
        try {
            $merchantId = $this->getMerchantId();
            
            $ticket = Ticket::forMerchant($merchantId)->findOrFail($ticketId);
            $subtask = $ticket->subtasks()->findOrFail($subtaskId);
            
            $subtask->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subtask deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subtask',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add comment to ticket
     */
    public function addComment(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'comment' => 'nullable|string',
                'parent_id' => 'nullable|exists:ticket_comments,id',
                'is_internal' => 'sometimes|boolean',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:10240' // 10MB max per file
            ]);

            // Require either comment text or attachments
            if (empty($validated['comment']) && !$request->hasFile('attachments')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment text or attachments are required'
                ], 422);
            }

            $merchantId = $this->getMerchantId();
            $ticket = Ticket::forMerchant($merchantId)->findOrFail($id);

            $comment = $ticket->comments()->create([
                'merchant_id' => $merchantId,
                'user_id' => auth()->id(),
                'comment' => $validated['comment'] ?? '',
                'parent_id' => $validated['parent_id'] ?? null,
                'is_internal' => $validated['is_internal'] ?? false
            ]);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('comments/attachments', $fileName, 'public');

                    $comment->attachments()->create([
                        'merchant_id' => $merchantId,
                        'ticket_id' => $ticket->id,
                        'user_id' => auth()->id(),
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => $comment->load(['user:id,name,email', 'attachments', 'voiceRecordings'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a comment
     */
    public function updateComment(Request $request, $id, $commentId)
    {
        try {
            $validated = $request->validate([
                'comment' => 'required|string'
            ]);

            $merchantId = $this->getMerchantId();
            $ticket = Ticket::forMerchant($merchantId)->findOrFail($id);
            
            $comment = $ticket->comments()->findOrFail($commentId);
            
            // Check if user owns the comment
            if ($comment->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to edit this comment'
                ], 403);
            }

            $comment->update([
                'comment' => $validated['comment']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully',
                'data' => $comment->load(['user:id,name,email', 'attachments'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a comment
     */
    public function deleteComment($id, $commentId)
    {
        try {
            $merchantId = $this->getMerchantId();
            $ticket = Ticket::forMerchant($merchantId)->findOrFail($id);
            
            $comment = $ticket->comments()->findOrFail($commentId);
            
            // Check if user owns the comment
            if ($comment->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this comment'
                ], 403);
            }

            // Delete associated attachments
            foreach ($comment->attachments as $attachment) {
                if (Storage::exists('public/' . $attachment->file_path)) {
                    Storage::delete('public/' . $attachment->file_path);
                }
                $attachment->delete();
            }

            // Delete the comment (this will also delete replies due to cascade)
            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload attachment
     */
    public function uploadAttachment(Request $request, $id)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240' // 10MB max
            ]);

            $merchantId = $this->getMerchantId();
            $ticket = Ticket::forMerchant($merchantId)->findOrFail($id);

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('tickets/attachments', $fileName, 'public');

            $attachment = $ticket->attachments()->create([
                'merchant_id' => $merchantId,
                'user_id' => auth()->id(),
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $attachment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete attachment
     */
    public function deleteAttachment($id, $attachmentId)
    {
        try {
            $merchantId = $this->getMerchantId();
            $ticket = Ticket::forMerchant($merchantId)->findOrFail($id);
            
            $attachment = $ticket->attachments()->where('id', $attachmentId)->firstOrFail();
            
            // Delete file from storage
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            
            // Delete database record
            $attachment->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attachment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload voice recording
     */
    public function uploadVoiceRecording(Request $request, $id)
    {
        try {
            $request->validate([
                'audio' => 'required|file|mimes:webm,mp3,wav,ogg|max:5120', // 5MB max
                'duration' => 'required|integer'
            ]);

            $merchantId = $this->getMerchantId();
            $ticket = Ticket::forMerchant($merchantId)->findOrFail($id);

            $file = $request->file('audio');
            $fileName = 'voice_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('tickets/voice-recordings', $fileName, 'public');

            // Create voice recording record
            $voiceRecording = $ticket->voiceRecordings()->create([
                'merchant_id' => $merchantId,
                'user_id' => auth()->id(),
                'file_name' => $fileName,
                'file_path' => $filePath,
                'duration' => $request->duration,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType()
            ]);

            // Log activity
            $ticket->logActivity(
                'voice_message_sent',
                auth()->user()->name . ' sent a voice message',
                [
                    'duration' => $request->duration,
                    'voice_recording_id' => $voiceRecording->id
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Voice recording uploaded successfully',
                'data' => $voiceRecording->load('user:id,name,email')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload voice recording',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteVoiceRecording($id, $voiceRecordingId)
    {
        try {
            $merchantId = $this->getMerchantId();
            $ticket = Ticket::forMerchant($merchantId)->findOrFail($id);
            $voiceRecording = $ticket->voiceRecordings()->findOrFail($voiceRecordingId);
            
            // Authorization check
            if ($voiceRecording->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this voice recording'
                ], 403);
            }
            
            // Delete file from storage
            if (Storage::disk('public')->exists($voiceRecording->file_path)) {
                Storage::disk('public')->delete($voiceRecording->file_path);
            }
            
            // Delete related activity
            $ticket->activities()
                ->where('activity_type', 'voice_message_sent')
                ->where('metadata->voice_recording_id', $voiceRecordingId)
                ->delete();
            
            // Log deletion activity
            $ticket->logActivity(
                'voice_message_deleted',
                auth()->user()->name . ' deleted a voice message',
                [
                    'voice_recording_id' => $voiceRecordingId,
                    'file_name' => $voiceRecording->file_name
                ]
            );
            
            // Delete voice recording record
            $voiceRecording->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Voice recording deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete voice recording',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start timer
     */
    public function startTimer($id)
    {
        try {
            $merchantId = $this->getMerchantId();
            $ticket = Ticket::forMerchant($merchantId)->findOrFail($id);

            // Check if there's already an active timer
            $activeTimer = $ticket->timeLogs()->active()->first();
            if ($activeTimer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Timer is already running'
                ], 400);
            }

            $timeLog = $ticket->timeLogs()->create([
                'merchant_id' => $merchantId,
                'user_id' => auth()->id(),
                'started_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Timer started successfully',
                'data' => $timeLog
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start timer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop timer
     */
    public function stopTimer($id)
    {
        try {
            $merchantId = $this->getMerchantId();
            $ticket = Ticket::forMerchant($merchantId)->findOrFail($id);

            $activeTimer = $ticket->timeLogs()->active()->where('user_id', auth()->id())->first();
            
            if (!$activeTimer) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active timer found'
                ], 400);
            }

            $activeTimer->stop();

            return response()->json([
                'success' => true,
                'message' => 'Timer stopped successfully',
                'data' => $activeTimer->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop timer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ticket statistics
     */
    public function statistics(Request $request)
    {
        try {
            $merchantId = $this->getMerchantId();

            $query = Ticket::forMerchant($merchantId);

            // Filter by workspace if provided
            if ($request->has('workspace_id') && $request->workspace_id !== 'all') {
                $query->where('workspace_id', $request->workspace_id);
            }

            $stats = [
                'total' => $query->count(),
                'by_status' => [
                    'backlog' => (clone $query)->byStatus('backlog')->count(),
                    'todo' => (clone $query)->byStatus('todo')->count(),
                    'in_progress' => (clone $query)->byStatus('in-progress')->count(),
                    'review' => (clone $query)->byStatus('review')->count(),
                    'complete' => (clone $query)->byStatus('complete')->count(),
                ],
                'by_priority' => [
                    'urgent' => (clone $query)->byPriority('urgent')->count(),
                    'high' => (clone $query)->byPriority('high')->count(),
                    'medium' => (clone $query)->byPriority('medium')->count(),
                    'low' => (clone $query)->byPriority('low')->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
