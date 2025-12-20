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
        Schema::create('family_infos', function (Blueprint $table) {
            $table->id();
            // Father Information
            $table->string('father_name');
            $table->string('father_cnic', 15)->unique(); // CNIC format: 12345-1234567-1
            $table->string('father_occupation')->nullable();
            
            // Mother Information
            $table->string('mother_name')->nullable();
            
            // Address
            $table->text('home_address');
            
            // Foreign Key
            $table->foreignId('student_id')
                  ->unique()
                  ->constrained('students')
                  ->cascadeOnDelete();
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
                    Schema::dropIfExists('family_infos');
    }
};
