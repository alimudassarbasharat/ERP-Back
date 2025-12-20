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
        Schema::create('ticket_subtasks', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_id');
            $table->unsignedBigInteger('ticket_id');
            $table->string('title');
            $table->boolean('completed')->default(false);
            $table->integer('position')->default(0); // For ordering
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('merchant_id');
            $table->index('ticket_id');
            $table->index('completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_subtasks');
    }
};
