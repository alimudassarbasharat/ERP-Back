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
        Schema::create('challans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('fee_invoice_id')->constrained('fee_invoices')->onDelete('cascade');
            $table->string('challan_no')->unique();
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->enum('status', ['unpaid', 'paid', 'cancelled', 'expired'])->default('unpaid');
            $table->string('pdf_path')->nullable();
            $table->foreignId('parent_challan_id')->nullable()->constrained('challans')->onDelete('set null')->comment('For reissue/supplementary');
            $table->text('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('cancelled_at')->nullable();
            $table->json('student_snapshot')->nullable();
            $table->json('class_snapshot')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('generated_at');
            $table->timestamps();
            
            $table->unique(['school_id', 'challan_no'], 'challans_school_challan_no_unique');
            $table->index(['school_id', 'status', 'due_date']);
            $table->index('fee_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challans');
    }
};
