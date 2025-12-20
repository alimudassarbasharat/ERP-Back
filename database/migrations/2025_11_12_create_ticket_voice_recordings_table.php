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
        Schema::create('ticket_voice_recordings', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('comment_id')->nullable(); // Link to comment if attached to one
            $table->unsignedBigInteger('user_id');
            $table->string('file_path'); // Storage path
            $table->string('file_name');
            $table->integer('duration')->nullable(); // Duration in seconds
            $table->integer('file_size')->nullable(); // File size in bytes
            $table->string('mime_type')->default('audio/webm');
            $table->text('transcription')->nullable(); // For future transcription feature
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('comment_id')->references('id')->on('ticket_comments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('merchant_id');
            $table->index('ticket_id');
            $table->index('comment_id');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_voice_recordings');
    }
};

