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
            if (!Schema::hasColumn('sessions', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (!Schema::hasColumn('sessions', 'end_date')) {
                $table->date('end_date')->nullable();
            }
            
            // Add is_active field (only one active session per school)
            if (!Schema::hasColumn('sessions', 'is_active')) {
                $table->boolean('is_active')->default(false);
            }
            
            // Add school relationship
            if (!Schema::hasColumn('sessions', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable();
            }
            
            // Add notes field
            if (!Schema::hasColumn('sessions', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
        
        // Update status enum to include more states (only if not already done)
        if (Schema::hasColumn('sessions', 'status')) {
            try {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->dropColumn('status');
                });
                
                Schema::table('sessions', function (Blueprint $table) {
                    $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
                });
            } catch (\Exception $e) {
                // Status column might already be the correct type
                echo "⚠️  Status column already updated or error: " . $e->getMessage() . "\n";
            }
        } else {
            Schema::table('sessions', function (Blueprint $table) {
                $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            });
        }
        
        // Add indexes for performance (skip if they exist)
        try {
            Schema::table('sessions', function (Blueprint $table) {
                $table->index(['school_id', 'is_active']);
                $table->index(['merchant_id', 'is_active']);
                $table->index(['start_date', 'end_date']);
                
                // Add foreign key constraint for school_id
                if (Schema::hasTable('schools') && Schema::hasColumn('sessions', 'school_id')) {
                    $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                }
            });
        } catch (\Exception $e) {
            echo "⚠️  Index or foreign key already exists: " . $e->getMessage() . "\n";
        }
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