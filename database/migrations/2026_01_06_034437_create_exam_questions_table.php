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
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_paper_id')->constrained('exam_papers')->onDelete('cascade');
            $table->string('section_name')->comment('MCQ / Short / Long');
            $table->text('question_text');
            $table->enum('question_type', ['mcq', 'short', 'long'])->default('short');
            $table->decimal('marks', 5, 2);
            $table->json('options_json')->nullable()->comment('MCQ options array');
            $table->string('answer_key')->nullable()->comment('Correct answer for MCQ');
            $table->integer('order_no')->default(0)->comment('Order within section');
            $table->timestamps();
            
            $table->index(['exam_paper_id', 'section_name']);
            $table->index(['exam_paper_id', 'order_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
