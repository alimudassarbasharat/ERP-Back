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
        Schema::create('user_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('offline'); // online, away, busy, offline
            $table->text('status_message')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('presence_data')->nullable(); // For additional presence info
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index(['status', 'last_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_statuses');
    }
}; 