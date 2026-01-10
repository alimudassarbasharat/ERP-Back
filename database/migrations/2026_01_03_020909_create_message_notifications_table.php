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
        Schema::create('message_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Recipient
            $table->unsignedBigInteger('message_id');
            $table->string('message_type'); // 'direct_message' or 'channel_message'
            $table->unsignedBigInteger('conversation_id');
            $table->string('conversation_type'); // 'dm' or 'channel'
            $table->string('conversation_name')->nullable();
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_name')->nullable();
            $table->text('message_preview')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('merchant_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'conversation_id']);
            $table->index('created_at');
            $table->index('merchant_id'); // CRITICAL: Index for tenant scoping
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_notifications');
    }
};
