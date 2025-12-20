<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeacherSubjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('teacher_subjects')) {
            Schema::create('teacher_subjects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teacher_id');
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->boolean('is_primary')->default(false); // Primary subject teacher
                $table->timestamps();
                
                $table->unique(['teacher_id', 'subject_id', 'academic_year_id']);
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
        Schema::dropIfExists('teacher_subjects');
    }
}