<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('student_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('created_by');
            $table->string('report_card'); // PDF file path
            $table->unsignedBigInteger('merchant_id');
            $table->timestamps();
            $table->softDeletes();


        });
    }

    public function down()
    {
        Schema::dropIfExists('report_cards');
    }
}; 