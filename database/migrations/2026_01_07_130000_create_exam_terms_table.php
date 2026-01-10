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
        Schema::create('exam_terms', function (Blueprint $table) {
            $table->id();
            if (Schema::hasTable('schools')) {
                $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            } else {
                $table->unsignedBigInteger('school_id')->nullable();
            }
            if (Schema::hasTable('sessions')) {
                $table->foreignId('session_id')->nullable()->constrained('sessions')->onDelete('cascade');
            } else {
                $table->unsignedBigInteger('session_id')->nullable();
            }
            $table->string('name'); // e.g., "Term-1", "Midterm", "Final"
            $table->string('code')->nullable(); // e.g., "T1", "MT", "F"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('order')->default(0)->comment('Display order');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['school_id', 'session_id', 'status']);
            $table->index(['session_id', 'status']);
            $table->unique(['school_id', 'session_id', 'name'], 'exam_terms_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_terms');
    }
};
