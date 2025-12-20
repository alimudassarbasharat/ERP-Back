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
            $table->string('first_name');
            $table->string('last_name');
            $table->string('roll_number')->unique();
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->string('cnic_number')->unique();
            $table->date('DOB');
            $table->date('DOA');
            $table->enum('religion', ['Islam', 'Christianity', 'Hinduism', 'Other']);
            $table->string('cast');
            $table->string('blood_group');
            $table->string('photo_path');
            $table->string('merchant_id');
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
