<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds class_id to sections table for one-to-many relationship:
     * - One Class can have Many Sections
     * - One Section belongs to One Class
     */
    public function up(): void
    {
        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $table) {
                // Add class_id if it doesn't exist
                if (!Schema::hasColumn('sections', 'class_id')) {
                    $table->unsignedBigInteger('class_id')->nullable()->after('id');
                    
                    // Add foreign key constraint
                    $table->foreign('class_id')
                        ->references('id')
                        ->on('classes')
                        ->onDelete('cascade');
                    
                    // Add index for performance
                    $table->index('class_id', 'sections_class_id_index');
                }
                
                // Remove unique constraint on name if exists (we'll make it unique per class)
                $indexes = $this->getIndexes('sections');
                if (in_array('sections_name_unique', $indexes)) {
                    $table->dropUnique('sections_name_unique');
                }
                
                // Add composite unique: name + class_id (allows same name in different classes)
                if (!$this->hasIndex('sections', 'sections_name_class_id_unique')) {
                    $table->unique(['name', 'class_id'], 'sections_name_class_id_unique');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $table) {
                // Drop unique constraint
                if ($this->hasIndex('sections', 'sections_name_class_id_unique')) {
                    $table->dropUnique('sections_name_class_id_unique');
                }
                
                // Drop foreign key and index
                if (Schema::hasColumn('sections', 'class_id')) {
                    $table->dropForeign(['class_id']);
                    $table->dropIndex('sections_class_id_index');
                    $table->dropColumn('class_id');
                }
                
                // Restore unique on name
                $table->unique('name', 'sections_name_unique');
            });
        }
    }
    
    /**
     * Check if an index exists (PostgreSQL compatible)
     */
    private function hasIndex($table, $indexName)
    {
        $connection = Schema::getConnection();
        
        try {
            // For PostgreSQL
            $result = \DB::select(
                "SELECT indexname 
                 FROM pg_indexes 
                 WHERE schemaname = 'public'
                 AND tablename = ? 
                 AND indexname = ?",
                [$table, $indexName]
            );
            
            return count($result) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get all indexes for a table (PostgreSQL compatible)
     */
    private function getIndexes($table)
    {
        $connection = Schema::getConnection();
        
        try {
            // For PostgreSQL
            $result = \DB::select(
                "SELECT indexname 
                 FROM pg_indexes 
                 WHERE schemaname = 'public'
                 AND tablename = ?",
                [$table]
            );
            
            return array_map(function($row) {
                return $row->indexname;
            }, $result);
        } catch (\Exception $e) {
            return [];
        }
    }
};
