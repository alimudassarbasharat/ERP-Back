<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('academic_records', function (Blueprint $table) {
            $table->id();
            // Academic Details
            $table->string('last_admission_no')->nullable(); // Previous school admission number
            $table->boolean('has_sibling')->default(false); // Sibling in same school
            $table->string('session'); // e.g. "2024-2025"
            
            // Foreign Keys
            // $table->foreignId('class_id')->constrained('classes'); // Links to classes table
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->string('merchant_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('academic_records');
    }
};
