<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubjectMarkSheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('subject_mark_sheets')) {
            Schema::create('subject_mark_sheets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('exam_id');
                $table->unsignedBigInteger('class_id')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->decimal('marks_obtained', 5, 2)->nullable();
                $table->decimal('max_marks', 5, 2);
                $table->decimal('min_marks', 5, 2);
                $table->string('grade')->nullable();
                $table->decimal('grade_points', 3, 2)->nullable();
                $table->enum('status', ['pending', 'completed', 'absent', 'debarred'])->default('pending');
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                
                $table->unique(['student_id', 'subject_id', 'exam_id']);
                $table->index(['class_id', 'exam_id']);
                $table->index('academic_year_id');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subject_mark_sheets');
    }
}