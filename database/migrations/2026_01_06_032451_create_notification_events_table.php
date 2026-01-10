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
        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->enum('type', ['fee_reminder']);
            $table->string('reference_type')->comment('e.g. challan');
            $table->unsignedBigInteger('reference_id')->comment('challan_id');
            $table->enum('trigger', ['before_due', 'on_due', 'after_due']);
            $table->dateTime('scheduled_at');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();
            
            $table->unique(['reference_type', 'reference_id', 'trigger'], 'notification_events_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_events');
    }
};
