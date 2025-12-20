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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('employee_code')->unique()->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('username')->nullable();
            $table->string('status')->default('active');
            $table->string('designation')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('qualification')->nullable();
            $table->string('specialization')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->date('joining_date')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->text('bank_account_details')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
