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
        Schema::table('sessions', function (Blueprint $table) {
            // Add start and end dates
            $table->date('start_date')->nullable()->after('description');
            $table->date('end_date')->nullable()->after('start_date');
            
            // Add is_active field (only one active session per school)
            $table->boolean('is_active')->default(false)->after('status');
            
            // Add school relationship
            $table->unsignedBigInteger('school_id')->nullable()->after('is_active');
            
            // Add notes field
            $table->text('notes')->nullable()->after('school_id');
            
            // Update status enum to include more states
            $table->dropColumn('status');
        });
        
        // Add the new status column with updated enum values
        Schema::table('sessions', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->after('is_active');
        });
        
        // Add indexes for performance
        Schema::table('sessions', function (Blueprint $table) {
            $table->index(['school_id', 'is_active']);
            $table->index(['merchant_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
            
            // Add foreign key constraint for school_id
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropIndex(['school_id', 'is_active']);
            $table->dropIndex(['merchant_id', 'is_active']);
            $table->dropIndex(['start_date', 'end_date']);
            
            $table->dropColumn([
                'start_date', 
                'end_date', 
                'is_active', 
                'school_id', 
                'notes',
                'status'
            ]);
            
            // Restore original status column
            $table->enum('status', ['active', 'inactive'])->default('active');
        });
    }
};