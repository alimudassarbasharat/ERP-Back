<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('fee_payments')) {
            Schema::create('fee_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fee_summary_id')->nullable();
                $table->unsignedBigInteger('student_id');
                $table->string('transaction_id')->unique();
                $table->decimal('amount', 10, 2);
                $table->string('payment_method'); // cash, card, bank_transfer, online
                $table->date('payment_date');
                $table->string('received_by')->nullable(); // staff who received payment
                $table->text('remarks')->nullable();
                $table->json('payment_details')->nullable(); // bank details, card last 4 digits, etc
                $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
                $table->timestamps();
                
                $table->index(['fee_summary_id', 'student_id']);
                $table->index('transaction_id');
                $table->index('payment_date');
                $table->index('status');
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
        Schema::dropIfExists('fee_payments');
    }
}