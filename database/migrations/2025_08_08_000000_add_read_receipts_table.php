<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReadReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_read_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at');
            $table->timestamps();
            
            $table->unique(['message_id', 'user_id']);
            $table->index(['message_id', 'read_at']);
        });
        
        Schema::create('direct_message_read_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('direct_message_id')->constrained('direct_messages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at');
            $table->timestamps();
            
            $table->unique(['direct_message_id', 'user_id']);
            $table->index(['direct_message_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('direct_message_read_receipts');
        Schema::dropIfExists('message_read_receipts');
    }
}