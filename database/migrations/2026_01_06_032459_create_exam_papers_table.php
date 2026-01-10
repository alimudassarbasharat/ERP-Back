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
        Schema::create('exam_papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            // Use subjects table (same as datesheet entries)
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->integer('total_marks')->default(0);
            $table->integer('passing_marks')->default(0);
            $table->date('exam_date')->nullable();
            $table->timestamps();
            
            $table->unique(['exam_id', 'class_id', 'subject_id'], 'exam_papers_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_papers');
    }
};
