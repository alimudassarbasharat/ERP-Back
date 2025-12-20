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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('public'); // public, private, direct
            $table->string('icon')->nullable();
            $table->string('color')->default('#4A154B'); // Slack-like default color
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // For channel-specific settings
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
}; 