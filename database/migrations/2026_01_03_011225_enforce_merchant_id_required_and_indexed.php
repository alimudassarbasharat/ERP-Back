<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Makes merchant_id required (not nullable) and adds indexes for performance
     * This ensures tenant isolation is enforced at the database level
     */
    public function up(): void
    {
        // Critical tables that MUST have merchant_id for tenant isolation
        $criticalTables = [
            // User & Authentication
            'users',
            'admins',
            
            // Messaging & Communication
            'channels',
            'channel_users',
            'messages',
            'message_reactions',
            'message_attachments',
            'direct_message_conversations',
            'direct_message_participants',
            'direct_messages',
            'direct_message_attachments',
            'mention_notifications',
            'user_presence',
            
            // Academic Data
            'students',
            'teachers',
            'classes',
            'sections',
            'subjects',
            'exams',
            'academic_years',
            'attendance_records',
            
            // Departments
            'departments',
        ];

        foreach ($criticalTables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // Make merchant_id required if it exists and is nullable
                if (Schema::hasColumn($tableName, 'merchant_id')) {
                    // First, backfill any null values with a default (use existing data's merchant_id if available)
                    // This is a safety measure - in production, you should manually backfill before running this
                    DB::statement("
                        UPDATE {$tableName} 
                        SET merchant_id = 'DEFAULT_TENANT' 
                        WHERE merchant_id IS NULL
                    ");
                    
                    // Change column to be not nullable
                    $table->string('merchant_id')->nullable(false)->change();
                    
                    // Add index if it doesn't exist
                    if (!$this->hasIndex($tableName, "{$tableName}_merchant_id_index")) {
                        $table->index('merchant_id', "{$tableName}_merchant_id_index");
                    }
                } else {
                    // Add merchant_id if it doesn't exist
                    $table->string('merchant_id')->nullable(false)->after('id');
                    $table->index('merchant_id', "{$tableName}_merchant_id_index");
                    
                    // Backfill with default (should be replaced with actual merchant_id)
                    DB::statement("
                        UPDATE {$tableName} 
                        SET merchant_id = 'DEFAULT_TENANT'
                    ");
                }
            });
        }

        // Pivot tables - add merchant_id if they don't have it
        $pivotTables = [
            'channel_users',
            'direct_message_participants',
            'teacher_subjects',
            'teacher_classes',
            'class_subjects',
        ];

        foreach ($pivotTables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'merchant_id')) {
                    // For pivot tables, merchant_id can be derived from related tables
                    // But we'll add it for direct querying
                    $table->string('merchant_id')->nullable()->after('id');
                    $table->index('merchant_id', "{$tableName}_merchant_id_index");
                }
            });
        }
    }

    /**
     * Check if an index exists
     */
    private function hasIndex($table, $indexName)
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        
        return $result[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We don't make merchant_id nullable again as this would break tenant isolation
        // If you need to rollback, you should manually handle this
    }
};
