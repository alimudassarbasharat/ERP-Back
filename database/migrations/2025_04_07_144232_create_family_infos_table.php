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
            $table->string('father_name')->nullable();
            $table->string('father_cnic', 25)->nullable(); // Max 25 for CNIC formats
            $table->string('father_occupation')->nullable();

            // Mother Information
            $table->string('mother_name')->nullable();
            $table->string('mother_cnic', 25)->nullable();
            $table->string('mother_occupation')->nullable();

            // Guardian Information
            $table->string('guardian_name')->nullable();
            $table->string('guardian_cnic', 25)->nullable();
            $table->string('guardian_occupation')->nullable();
            $table->string('guardian_relationship')->nullable();

            // Address & Contact
            $table->text('home_address')->nullable();
            $table->string('emergency_contact', 20)->nullable();

            // Family Details
            $table->decimal('monthly_income', 15, 2)->nullable(); // decimal for income
            $table->integer('family_members')->nullable();

            // Foreign Key
            $table->foreignId('student_id')
                ->unique()
                ->constrained('students')
                ->cascadeOnDelete();

            $table->string('merchant_id')->nullable();

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
