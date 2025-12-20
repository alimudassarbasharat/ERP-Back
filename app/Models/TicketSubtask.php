<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketSubtask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'ticket_id',
        'title',
        'completed',
        'position',
        'completed_by',
        'completed_at'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'position' => 'integer',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function completedByUser()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Mark subtask as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'completed' => true,
            'completed_by' => auth()->id(),
            'completed_at' => now()
        ]);

        // Log activity
        $this->ticket->logActivity('subtask_completed', auth()->user()->name . ' completed subtask: ' . $this->title);
    }

    /**
     * Mark subtask as incomplete
     */
    public function markAsIncomplete()
    {
        $this->update([
            'completed' => false,
            'completed_by' => null,
            'completed_at' => null
        ]);
    }
}
