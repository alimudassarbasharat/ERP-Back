<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds class_id and section_id columns to students table if they don't exist
     */
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                // Add class_id if it doesn't exist
                if (!Schema::hasColumn('students', 'class_id')) {
                    $table->unsignedBigInteger('class_id')->nullable()->after('merchant_id');
                    
                    // Add foreign key constraint if classes table exists
                    if (Schema::hasTable('classes')) {
                        $table->foreign('class_id')
                            ->references('id')
                            ->on('classes')
                            ->onDelete('set null');
                    }
                    
                    // Add index for performance
                    $table->index('class_id', 'students_class_id_index');
                }
                
                // Add section_id if it doesn't exist
                if (!Schema::hasColumn('students', 'section_id')) {
                    $table->unsignedBigInteger('section_id')->nullable()->after('class_id');
                    
                    // Add foreign key constraint if sections table exists
                    if (Schema::hasTable('sections')) {
                        $table->foreign('section_id')
                            ->references('id')
                            ->on('sections')
                            ->onDelete('set null');
                    }
                    
                    // Add index for performance
                    $table->index('section_id', 'students_section_id_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                // Drop foreign keys first
                if (Schema::hasColumn('students', 'section_id')) {
                    $foreignKeys = $this->getForeignKeys('students');
                    if (in_array('students_section_id_foreign', $foreignKeys)) {
                        $table->dropForeign('students_section_id_foreign');
                    }
                    $table->dropIndex('students_section_id_index');
                    $table->dropColumn('section_id');
                }
                
                if (Schema::hasColumn('students', 'class_id')) {
                    $foreignKeys = $this->getForeignKeys('students');
                    if (in_array('students_class_id_foreign', $foreignKeys)) {
                        $table->dropForeign('students_class_id_foreign');
                    }
                    $table->dropIndex('students_class_id_index');
                    $table->dropColumn('class_id');
                }
            });
        }
    }
    
    /**
     * Get foreign key names for a table
     */
    private function getForeignKeys(string $table): array
    {
        $foreignKeys = [];
        try {
            $constraints = DB::select("
                SELECT constraint_name 
                FROM information_schema.table_constraints 
                WHERE table_name = ? 
                AND constraint_type = 'FOREIGN KEY'
            ", [$table]);
            
            foreach ($constraints as $constraint) {
                $foreignKeys[] = $constraint->constraint_name;
            }
        } catch (\Exception $e) {
            // If query fails, return empty array
        }
        return $foreignKeys;
    }
};
