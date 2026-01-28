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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('roll_number')->unique();
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->string('cnic_number')->unique();
            $table->date('date_of_birth')->nullable();
            $table->date('admission_date')->nullable();
            $table->enum('religion', ['Islam', 'Christianity', 'Hinduism', 'Other']);
            $table->string('cast')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('merchant_id')->nullable();
            // $table->foreignId('class_id')->constrained('classes');
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
        Schema::dropIfExists('students');
    }
};
