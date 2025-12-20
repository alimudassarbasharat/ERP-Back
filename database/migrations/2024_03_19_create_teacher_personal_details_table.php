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
        Schema::create('teacher_personal_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('cnic')->unique()->nullable();
            $table->string('religion')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('profile_picture')->nullable();
            $table->timestamps();
            $table->softDeletes();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_personal_details');
    }
};
