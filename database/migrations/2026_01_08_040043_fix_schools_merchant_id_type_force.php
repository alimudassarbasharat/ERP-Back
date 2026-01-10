<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // For PostgreSQL, we need to drop and recreate the column
            // First, drop indexes that include merchant_id
            try {
                DB::statement('DROP INDEX IF EXISTS schools_merchant_id_index');
                DB::statement('DROP INDEX IF EXISTS schools_completed_at_merchant_id_index');
            } catch (\Exception $e) {
                // Indexes might not exist or have different names
            }
            
            // Drop the column
            DB::statement('ALTER TABLE schools DROP COLUMN IF EXISTS merchant_id');
            
            // Add it back as varchar (string)
            DB::statement('ALTER TABLE schools ADD COLUMN merchant_id VARCHAR(255) NULL');
            
            // Recreate indexes
            DB::statement('CREATE INDEX schools_merchant_id_index ON schools(merchant_id)');
            DB::statement('CREATE INDEX schools_completed_at_merchant_id_index ON schools(completed_at, merchant_id)');
        } else {
            // For MySQL/MariaDB
            Schema::table('schools', function (Blueprint $table) {
                $table->string('merchant_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            try {
                DB::statement('DROP INDEX IF EXISTS schools_merchant_id_index');
                DB::statement('DROP INDEX IF EXISTS schools_completed_at_merchant_id_index');
            } catch (\Exception $e) {
                // Ignore
            }
            
            DB::statement('ALTER TABLE schools DROP COLUMN IF EXISTS merchant_id');
            DB::statement('ALTER TABLE schools ADD COLUMN merchant_id BIGINT NULL');
            
            DB::statement('CREATE INDEX schools_merchant_id_index ON schools(merchant_id)');
            DB::statement('CREATE INDEX schools_completed_at_merchant_id_index ON schools(completed_at, merchant_id)');
        } else {
            Schema::table('schools', function (Blueprint $table) {
                $table->unsignedBigInteger('merchant_id')->nullable()->change();
            });
        }
    }
};