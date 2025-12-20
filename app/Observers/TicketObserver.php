<?php

namespace App\Observers;

use App\Models\Ticket;

class TicketObserver
{
    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        // Log ticket creation
        $user = auth()->user();
        if ($user) {
            $ticket->activities()->create([
                'merchant_id' => $ticket->merchant_id,
                'user_id' => $user->id,
                'activity_type' => 'created',
                'description' => '<strong>' . $user->name . '</strong> created this ticket',
                'metadata' => [
                    'priority' => $ticket->priority,
                    'category' => $ticket->category,
                    'status' => $ticket->status
                ]
            ]);
        }
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        $user = auth()->user();
        if (!$user) return;

        $changes = $ticket->getChanges();
        $original = $ticket->getOriginal();

        // Log status change
        if (isset($changes['status']) && isset($original['status'])) {
            $oldStatus = $this->formatStatus($original['status']);
            $newStatus = $this->formatStatus($changes['status']);
            
            $ticket->activities()->create([
                'merchant_id' => $ticket->merchant_id,
                'user_id' => $user->id,
                'activity_type' => 'status_changed',
                'description' => '<strong>' . $user->name . '</strong> changed status from <span class="status-badge-inline">' . $oldStatus . '</span> to <span class="status-badge-inline">' . $newStatus . '</span>',
                'metadata' => [
                    'old_status' => $original['status'],
                    'new_status' => $changes['status']
                ]
            ]);
        }

        // Log priority change
        if (isset($changes['priority']) && isset($original['priority'])) {
            $oldPriority = ucfirst($original['priority']);
            $newPriority = ucfirst($changes['priority']);
            
            $ticket->activities()->create([
                'merchant_id' => $ticket->merchant_id,
                'user_id' => $user->id,
                'activity_type' => 'priority_changed',
                'description' => '<strong>' . $user->name . '</strong> changed priority from ' . $oldPriority . ' to ' . $newPriority,
                'metadata' => [
                    'old_priority' => $original['priority'],
                    'new_priority' => $changes['priority']
                ]
            ]);
        }

        // Log assignee change
        if (isset($changes['assignee_id'])) {
            $oldAssignee = 'Unassigned';
            $newAssignee = 'Unassigned';

            if (isset($original['assignee_id']) && $original['assignee_id']) {
                $oldUser = \App\Models\User::find($original['assignee_id']);
                if ($oldUser) {
                    $oldAssignee = $oldUser->name;
                }
            }

            if ($changes['assignee_id']) {
                $newUser = \App\Models\User::find($changes['assignee_id']);
                if ($newUser) {
                    $newAssignee = $newUser->name;
                }
            }

            $ticket->activities()->create([
                'merchant_id' => $ticket->merchant_id,
                'user_id' => $user->id,
                'activity_type' => 'assignee_changed',
                'description' => '<strong>' . $user->name . '</strong> assigned to <strong>' . $newAssignee . '</strong>',
                'metadata' => [
                    'old_assignee_id' => $original['assignee_id'] ?? null,
                    'new_assignee_id' => $changes['assignee_id'] ?? null
                ]
            ]);
        }

        // Log title update
        if (isset($changes['title']) && isset($original['title'])) {
            $ticket->activities()->create([
                'merchant_id' => $ticket->merchant_id,
                'user_id' => $user->id,
                'activity_type' => 'title_updated',
                'description' => '<strong>' . $user->name . '</strong> updated the title',
                'metadata' => [
                    'old_title' => $original['title'],
                    'new_title' => $changes['title']
                ]
            ]);
        }

        // Log description update
        if (isset($changes['description']) && isset($original['description'])) {
            $ticket->activities()->create([
                'merchant_id' => $ticket->merchant_id,
                'user_id' => $user->id,
                'activity_type' => 'description_updated',
                'description' => '<strong>' . $user->name . '</strong> updated the description',
                'metadata' => []
            ]);
        }
    }

    /**
     * Format status for display
     */
    private function formatStatus($status)
    {
        return ucwords(str_replace('-', ' ', $status));
    }

    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "restored" event.
     */
    public function restored(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "force deleted" event.
     */
    public function forceDeleted(Ticket $ticket): void
    {
        //
    }
}
