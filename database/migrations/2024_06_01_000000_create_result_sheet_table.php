<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('result_sheet', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('exam_id');
            $table->integer('total_makr_obtains');
            $table->integer('total_marks');
            $table->string('percentage')->nullable();
            $table->string('grade')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();


        });
    }

    public function down()
    {
        Schema::dropIfExists('result_sheet');
    }
}; 