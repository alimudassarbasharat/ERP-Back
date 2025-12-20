<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('late_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->decimal('late_fee_amount', 10, 2)->default(0.00);
            $table->integer('apply_after_days')->default(5);
            $table->boolean('is_auto_apply')->default(true);
            $table->unsignedBigInteger('added_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('late_fees');
    }
}; 