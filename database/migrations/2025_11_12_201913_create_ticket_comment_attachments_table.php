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
        Schema::create('ticket_comment_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_id');
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('comment_id')->constrained('ticket_comments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ticket_id', 'comment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_comment_attachments');
    }
};
