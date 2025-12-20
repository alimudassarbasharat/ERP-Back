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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_id');
            $table->string('ticket_number')->unique(); // TKT-001
            $table->unsignedBigInteger('workspace_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['backlog', 'todo', 'in-progress', 'review', 'complete'])->default('todo');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('category')->nullable();
            $table->unsignedBigInteger('reporter_id'); // User who created ticket
            $table->unsignedBigInteger('assignee_id')->nullable(); // User assigned to ticket
            $table->integer('total_time_tracked')->default(0); // in seconds
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assignee_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('merchant_id');
            $table->index('workspace_id');
            $table->index('status');
            $table->index('priority');
            $table->index('assignee_id');
            $table->index('reporter_id');
            $table->index('ticket_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
