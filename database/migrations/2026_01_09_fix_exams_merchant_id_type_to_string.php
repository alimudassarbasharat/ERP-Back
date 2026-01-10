<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * CRITICAL FIX: Change exams.merchant_id from bigint to string
     * 
     * PROBLEM:
     * - exams table has merchant_id as unsignedBigInteger
     * - BUT users/admins/other tables use STRING merchant_id
     * - This causes PostgreSQL error: "invalid input syntax for type bigint"
     * 
     * SOLUTION:
     * - Change exams.merchant_id to string to match system-wide standard
     * - Preserve existing data
     */
    public function up(): void
    {
        if (!Schema::hasTable('exams')) {
            echo "⏭️  Table 'exams' doesn't exist, skipping.\n";
            return;
        }

        echo "🔧 Fixing exams.merchant_id type mismatch...\n";

        // Check current column type
        $column = DB::selectOne("
            SELECT data_type 
            FROM information_schema.columns 
            WHERE table_name = 'exams' 
            AND column_name = 'merchant_id'
        ");

        if ($column && $column->data_type === 'bigint') {
            echo "  Current type: bigint ❌\n";
            echo "  Changing to: string ✅\n";
            
            Schema::table('exams', function (Blueprint $table) {
                // Change from unsignedBigInteger to string
                $table->string('merchant_id')->nullable()->change();
            });
            
            echo "  ✅ merchant_id changed to string\n";
        } else {
            echo "  ✓ merchant_id already correct type\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('exams')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->unsignedBigInteger('merchant_id')->nullable()->change();
            });
        }
    }
};
