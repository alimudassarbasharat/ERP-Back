<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_activities', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id'); // Who performed the action
            $table->enum('activity_type', [
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
                'title_updated'
            ]);
            $table->text('description'); // Activity description
            $table->json('metadata')->nullable(); // Additional data (old_value, new_value, etc.)
            $table->timestamps();

            // Foreign keys
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('merchant_id');
            $table->index('ticket_id');
            $table->index('user_id');
            $table->index('activity_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_activities');
    }
};
