<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeeSummariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('fee_summaries')) {
            Schema::create('fee_summaries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('class_id')->nullable();
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->string('fee_type'); // tuition, transport, lab, etc.
                $table->decimal('total_amount', 10, 2);
                $table->decimal('paid_amount', 10, 2)->default(0);
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('fine_amount', 10, 2)->default(0);
                $table->decimal('balance_amount', 10, 2);
                $table->date('due_date');
                $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
                $table->json('metadata')->nullable(); // Additional data
                $table->timestamps();
                
                $table->index(['student_id', 'academic_year_id']);
                $table->index('status');
                $table->index('due_date');
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
        Schema::dropIfExists('fee_summaries');
    }
}