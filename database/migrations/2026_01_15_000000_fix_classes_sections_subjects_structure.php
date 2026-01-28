<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use \Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fixes the database structure for classes, sections, and subjects:
     * 1. Adds class_id foreign key to sections table
     * 2. Ensures proper indexes and unique constraints
     * 3. Fixes class_subjects pivot table structure
     */
    public function up(): void
    {
        // Fix sections table: Add class_id foreign key
        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $table) {
                // Add class_id if it doesn't exist
                if (!Schema::hasColumn('sections', 'class_id')) {
                    $table->unsignedBigInteger('class_id')->nullable()->after('id');
                }
                
                // Add foreign key constraint if it doesn't exist
                $foreignKeys = $this->getForeignKeys('sections');
                if (!in_array('sections_class_id_foreign', $foreignKeys)) {
                    $table->foreign('class_id')
                        ->references('id')
                        ->on('classes')
                        ->onDelete('cascade');
                }
                
                // Add index for class_id if it doesn't exist
                if (!$this->hasIndex('sections', 'sections_class_id_index')) {
                    $table->index('class_id', 'sections_class_id_index');
                }
                
                // Add unique constraint: section name must be unique per class
                // Remove old unique constraint on name if it exists
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
        
        // Fix classes table: Ensure proper structure
        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                // Ensure name column exists and is indexed
                if (!Schema::hasColumn('classes', 'name')) {
                    $table->string('name')->nullable()->after('id');
                }
                
                // Add index for name if it doesn't exist
                if (!$this->hasIndex('classes', 'classes_name_index')) {
                    $table->index('name', 'classes_name_index');
                }
                
                // Ensure merchant_id is indexed
                if (Schema::hasColumn('classes', 'merchant_id') && !$this->hasIndex('classes', 'classes_merchant_id_index')) {
                    $table->index('merchant_id', 'classes_merchant_id_index');
                }
            });
        }
        
        // Fix subjects table: Ensure proper structure
        if (Schema::hasTable('subjects')) {
            Schema::table('subjects', function (Blueprint $table) {
                // Ensure code column exists (for unique constraint)
                if (!Schema::hasColumn('subjects', 'code')) {
                    $table->string('code')->nullable()->after('name');
                }
                
                // Add index for name if it doesn't exist
                if (!$this->hasIndex('subjects', 'subjects_name_index')) {
                    $table->index('name', 'subjects_name_index');
                }
                
                // Ensure merchant_id is indexed
                if (Schema::hasColumn('subjects', 'merchant_id') && !$this->hasIndex('subjects', 'subjects_merchant_id_index')) {
                    $table->index('merchant_id', 'subjects_merchant_id_index');
                }
            });
        }
        
        // Fix class_subjects pivot table (ensure it's named correctly)
        $pivotTableName = 'class_subjects';
        if (!Schema::hasTable($pivotTableName) && Schema::hasTable('class_subject')) {
            // Rename class_subject to class_subjects
            Schema::rename('class_subject', $pivotTableName);
        }
        
        if (Schema::hasTable($pivotTableName)) {
            Schema::table($pivotTableName, function (Blueprint $table) use ($pivotTableName) {
                // Ensure foreign keys exist
                $foreignKeys = $this->getForeignKeys($pivotTableName);
                
                if (!in_array("{$pivotTableName}_class_id_foreign", $foreignKeys)) {
                    $table->foreign('class_id')
                        ->references('id')
                        ->on('classes')
                        ->onDelete('cascade');
                }
                
                if (!in_array("{$pivotTableName}_subject_id_foreign", $foreignKeys)) {
                    $table->foreign('subject_id')
                        ->references('id')
                        ->on('subjects')
                        ->onDelete('cascade');
                }
                
                // Add unique constraint: prevent duplicate subject assignments to same class
                if (!$this->hasIndex($pivotTableName, "{$pivotTableName}_class_id_subject_id_unique")) {
                    $table->unique(['class_id', 'subject_id'], "{$pivotTableName}_class_id_subject_id_unique");
                }
                
                // Add indexes for performance
                if (!$this->hasIndex($pivotTableName, "{$pivotTableName}_class_id_index")) {
                    $table->index('class_id', "{$pivotTableName}_class_id_index");
                }
                
                if (!$this->hasIndex($pivotTableName, "{$pivotTableName}_subject_id_index")) {
                    $table->index('subject_id', "{$pivotTableName}_subject_id_index");
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We don't fully reverse to avoid breaking existing data
        // If needed, manually handle rollback
    }
    
    /**
     * Check if an index exists
     */
    private function hasIndex($table, $indexName)
    {
        $connection = Schema::getConnection();
        
        try {
            // PostgreSQL-compatible index check
            if ($connection->getDriverName() === 'pgsql') {
                $result = DB::select(
                    "SELECT COUNT(*) as count 
                     FROM pg_indexes 
                     WHERE schemaname = 'public'
                     AND tablename = ? 
                     AND indexname = ?",
                    [$table, $indexName]
                );
            } else {
                // MySQL fallback
                $databaseName = $connection->getDatabaseName();
                $result = DB::select(
                    "SELECT COUNT(*) as count 
                     FROM information_schema.statistics 
                     WHERE table_schema = ? 
                     AND table_name = ? 
                     AND index_name = ?",
                    [$databaseName, $table, $indexName]
                );
            }
            
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get all indexes for a table
     */
    private function getIndexes($table)
    {
        $connection = Schema::getConnection();
        
        try {
            // PostgreSQL-compatible index retrieval
            if ($connection->getDriverName() === 'pgsql') {
                $result = DB::select(
                    "SELECT indexname as index_name 
                     FROM pg_indexes 
                     WHERE schemaname = 'public'
                     AND tablename = ?",
                    [$table]
                );
            } else {
                // MySQL fallback
                $databaseName = $connection->getDatabaseName();
                $result = DB::select(
                    "SELECT index_name 
                     FROM information_schema.statistics 
                     WHERE table_schema = ? 
                     AND table_name = ?",
                    [$databaseName, $table]
                );
            }
            
            return array_map(function($row) {
                return $row->index_name;
            }, $result);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get all foreign keys for a table (PostgreSQL compatible)
     */
    private function getForeignKeys($table)
    {
        $connection = Schema::getConnection();
        
        try {
            // PostgreSQL-compatible foreign key retrieval
            if ($connection->getDriverName() === 'pgsql') {
                $result = DB::select(
                    "SELECT constraint_name 
                     FROM information_schema.table_constraints 
                     WHERE table_schema = 'public'
                     AND table_name = ? 
                     AND constraint_type = 'FOREIGN KEY'",
                    [$table]
                );
            } else {
                // MySQL fallback
                $databaseName = $connection->getDatabaseName();
                $result = DB::select(
                    "SELECT constraint_name 
                     FROM information_schema.table_constraints 
                     WHERE table_schema = ? 
                     AND table_name = ? 
                     AND constraint_type = 'FOREIGN KEY'",
                    [$databaseName, $table]
                );
            }
            
            return array_map(function($row) {
                return $row->constraint_name;
            }, $result);
        } catch (\Exception $e) {
            return [];
        }
    }
};
