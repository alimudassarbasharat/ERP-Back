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
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('notification_events')->onDelete('cascade');
            $table->enum('channel', ['whatsapp', 'sms', 'email']);
            $table->string('recipient');
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->text('provider_response')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
            
            $table->index(['event_id', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};
