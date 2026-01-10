<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sibling_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('sessions')->onDelete('cascade');
            $table->integer('min_siblings')->comment('e.g. 2');
            $table->enum('discount_type', ['fixed', 'percentage']);
            $table->decimal('discount_value', 10, 2);
            $table->enum('apply_on', ['tuition', 'all', 'specific_head']);
            $table->foreignId('specific_fee_head_id')->nullable()->constrained('fee_heads')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['school_id', 'session_id', 'min_siblings']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sibling_discounts');
    }
};
