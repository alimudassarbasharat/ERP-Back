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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('preference_type'); // 'report_template'
            $table->string('preference_key');   // 'character', 'challan', 'idCard', etc.
            $table->string('preference_value'); // selected template value
            $table->json('metadata')->nullable(); // additional data
            $table->timestamps();
            

            $table->unique(['user_id', 'preference_type', 'preference_key']);
            
            $table->index(['user_id', 'preference_type']);
            $table->index(['preference_type', 'preference_key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_preferences');
    }
}; 