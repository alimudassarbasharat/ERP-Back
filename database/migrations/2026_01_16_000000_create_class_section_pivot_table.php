<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates pivot table for many-to-many relationship between classes and sections.
     * This allows:
     * - One section to belong to multiple classes
     * - One class to have multiple sections
     */
    public function up(): void
    {
        if (!Schema::hasTable('class_section')) {
            Schema::create('class_section', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('section_id');
                $table->timestamps();
                $table->softDeletes();
                
                // Foreign keys
                $table->foreign('class_id')
                    ->references('id')
                    ->on('classes')
                    ->onDelete('cascade');
                    
                $table->foreign('section_id')
                    ->references('id')
                    ->on('sections')
                    ->onDelete('cascade');
                
                // Unique constraint: prevent duplicate class-section pairs
                $table->unique(['class_id', 'section_id'], 'class_section_unique');
                
                // Indexes for performance
                $table->index('class_id', 'class_section_class_id_index');
                $table->index('section_id', 'class_section_section_id_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_section');
    }
};
