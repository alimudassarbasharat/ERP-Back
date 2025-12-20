<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelsTableSimple extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('channels')) {
            Schema::create('channels', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->text('description')->nullable();
                $table->enum('type', ['public', 'private'])->default('public');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->boolean('is_archived')->default(false);
                $table->json('settings')->nullable();
                $table->timestamps();
                
                $table->index('name');
                $table->index('type');
                $table->index('is_archived');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channels');
    }
}