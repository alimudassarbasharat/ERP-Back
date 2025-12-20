<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeacherClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('teacher_classes')) {
            Schema::create('teacher_classes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teacher_id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('section_id')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->enum('role', ['class_teacher', 'subject_teacher', 'assistant'])->default('subject_teacher');
                $table->timestamps();
                
                $table->unique(['teacher_id', 'class_id', 'section_id', 'subject_id', 'academic_year_id'], 'teacher_class_unique');
                $table->index('role');
                $table->index('academic_year_id');
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
        Schema::dropIfExists('teacher_classes');
    }
}