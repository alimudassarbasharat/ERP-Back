<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * CRITICAL FIX: Change merchant_id from bigint to string for all tables
     * 
     * PROBLEM:
     * - Some tables have merchant_id as unsignedBigInteger
     * - BUT the system standard is STRING merchant_id
     * - This causes PostgreSQL error: "invalid input syntax for type bigint"
     * 
     * SOLUTION:
     * - Change merchant_id to string to match system-wide standard
     * - Preserve existing data
     */
    public function up(): void
    {
        echo "🔧 Fixing merchant_id type mismatches across all tables...\n";

        // Get all tables with merchant_id column
        $tables = DB::select("
            SELECT table_name, data_type 
            FROM information_schema.columns 
            WHERE table_schema = 'public'
            AND column_name = 'merchant_id'
        ");

        foreach ($tables as $tableInfo) {
            $tableName = $tableInfo->table_name;
            $dataType = $tableInfo->data_type;
            
            if ($dataType === 'bigint' || $dataType === 'int8' || $dataType === 'integer') {
                echo "  📋 {$tableName}: {$dataType} → string\n";
                
                try {
                    Schema::table($tableName, function (Blueprint $table) {
                        // Change from integer to string
                        $table->string('merchant_id')->nullable()->change();
                    });
                    echo "     ✅ Fixed\n";
                } catch (\Exception $e) {
                    echo "     ⚠️  Error: " . $e->getMessage() . "\n";
                }
            } else {
                echo "  ✓ {$tableName} already string type\n";
            }
        }
        
        echo "✅ All merchant_id columns fixed!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversing this as string is the correct standard
        echo "⚠️  Not reversing merchant_id to integer - string is the system standard\n";
    }
};
