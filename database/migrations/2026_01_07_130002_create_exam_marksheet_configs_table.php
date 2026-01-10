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
        Schema::create('exam_marksheet_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            if (Schema::hasTable('schools')) {
                $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            } else {
                $table->unsignedBigInteger('school_id')->nullable();
            }
            $table->json('class_ids')->comment('Array of class IDs included in marksheet');
            $table->json('subject_ids')->comment('Array of subject IDs included in marksheet');
            $table->enum('total_marks_mode', ['same_for_all', 'per_subject'])->default('same_for_all');
            $table->integer('total_marks')->nullable()->comment('Used when total_marks_mode is same_for_all');
            $table->json('subject_totals')->nullable()->comment('JSON: {subject_id: total_marks} when per_subject mode');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['exam_id', 'school_id']);
            $table->unique('exam_id', 'exam_marksheet_config_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_marksheet_configs');
    }
};
