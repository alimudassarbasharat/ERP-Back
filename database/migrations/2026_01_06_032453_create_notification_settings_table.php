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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained('schools')->onDelete('cascade');
            $table->boolean('enable_whatsapp')->default(true);
            $table->boolean('enable_sms')->default(true);
            $table->boolean('enable_email')->default(true);
            $table->integer('days_before_due')->default(3);
            $table->integer('days_after_due')->default(5);
            $table->text('message_template')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
