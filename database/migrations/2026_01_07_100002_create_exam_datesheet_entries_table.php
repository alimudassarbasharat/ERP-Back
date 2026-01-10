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
        Schema::create('exam_datesheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('datesheet_id')->constrained('exam_datesheets')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room_id')->nullable();
            $table->string('room_name')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('invigilator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('paper_id')->nullable()->constrained('exam_papers')->onDelete('set null');
            $table->integer('total_marks')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('has_conflict')->default(false);
            $table->text('conflict_details')->nullable();
            $table->timestamps();
            
            // Critical indexes for conflict detection
            $table->index(['exam_id', 'exam_date']);
            $table->index(['class_id', 'section_id', 'exam_date']);
            $table->index(['room_id', 'exam_date']);
            $table->index(['supervisor_id', 'exam_date']);
            $table->index(['invigilator_id', 'exam_date']);
            $table->index(['datesheet_id', 'has_conflict']);
            
            // Unique constraint: same class/section/subject can't have duplicate entries
            $table->unique(['datesheet_id', 'class_id', 'section_id', 'subject_id'], 'datesheet_entry_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_datesheet_entries');
    }
};
