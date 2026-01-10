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
        Schema::create('fee_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_invoice_id')->constrained('fee_invoices')->onDelete('cascade');
            $table->string('fee_head_name')->comment('Snapshot of fee head name');
            $table->foreignId('fee_head_id')->nullable()->constrained('fee_heads')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            
            $table->index('fee_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_invoice_items');
    }
};
