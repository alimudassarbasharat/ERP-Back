<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing constraint
        DB::statement('ALTER TABLE ticket_activities DROP CONSTRAINT IF EXISTS ticket_activities_activity_type_check');
        
        // Add new constraint with additional value
        DB::statement("ALTER TABLE ticket_activities ADD CONSTRAINT ticket_activities_activity_type_check CHECK (activity_type IN (
            'created',
            'status_changed',
            'priority_changed',
            'assignee_changed',
            'comment_added',
            'timer_started',
            'timer_stopped',
            'subtask_added',
            'subtask_completed',
            'attachment_added',
            'description_updated',
            'title_updated',
            'voice_message_sent',
            'voice_message_deleted'
        ))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new constraint
        DB::statement('ALTER TABLE ticket_activities DROP CONSTRAINT IF EXISTS ticket_activities_activity_type_check');
        
        // Restore old constraint without voice_message_deleted
        DB::statement("ALTER TABLE ticket_activities ADD CONSTRAINT ticket_activities_activity_type_check CHECK (activity_type IN (
            'created',
            'status_changed',
            'priority_changed',
            'assignee_changed',
            'comment_added',
            'timer_started',
            'timer_stopped',
            'subtask_added',
            'subtask_completed',
            'attachment_added',
            'description_updated',
            'title_updated',
            'voice_message_sent'
        ))");
    }
};
