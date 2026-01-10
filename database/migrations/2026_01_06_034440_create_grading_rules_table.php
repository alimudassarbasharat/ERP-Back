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
        Schema::create('grading_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('sessions')->onDelete('cascade')->comment('Nullable for global rules');
            $table->decimal('min_percentage', 5, 2);
            $table->decimal('max_percentage', 5, 2);
            $table->string('grade', 10)->comment('A+, A, B+, B, etc.');
            $table->decimal('gpa', 3, 2)->nullable()->comment('Grade Point Average');
            $table->integer('order_no')->default(0)->comment('For ordering grades');
            $table->timestamps();
            
            $table->index(['school_id', 'session_id']);
            $table->unique(['school_id', 'session_id', 'grade'], 'grading_rules_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_rules');
    }
};
