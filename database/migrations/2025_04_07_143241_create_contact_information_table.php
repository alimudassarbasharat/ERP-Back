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
        Schema::create('contact_information', function (Blueprint $table) {
            $table->id();
            
            // Contact Details
            $table->string('reporting_number')->nullable();  // Primary contact (landline/mobile)
            $table->string('whatsapp_number')->nullable();   // WhatsApp-specific number
            $table->string('email')->nullable();
            
            // Address
            $table->text('address');
            $table->enum('province', ['Punjab', 'Sindh', 'KPK', 'Balochistan', 'AJK', 'GB', 'Islamabad']);
            $table->string('district');
            $table->string('city');
            $table->string('village')->nullable();           // Optional for rural addresses
            $table->string('postal_code')->nullable();       // Pakistan postal code (e.g., 54000)
            
            // Foreign Key
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();  // Delete contact if student is deleted
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
        Schema::dropIfExists('contact_information');
    }
};
