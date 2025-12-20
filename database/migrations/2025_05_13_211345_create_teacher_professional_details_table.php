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
        Schema::create('teacher_professional_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->string('designation')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('qualification')->nullable();
            $table->string('specialization')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->date('joining_date')->nullable();
            $table->decimal('salary', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_professional_details');
    }
};
