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
        Schema::create('challan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challan_id')->constrained('challans')->onDelete('cascade');
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->enum('payment_method', ['bank', 'cash', 'online']);
            $table->string('transaction_ref')->nullable();
            $table->decimal('paid_amount', 10, 2);
            $table->dateTime('paid_at');
            $table->json('provider_payload')->nullable();
            $table->timestamps();
            
            $table->index('challan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challan_payments');
    }
};
