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
        // For PostgreSQL, we need to drop and recreate the column
        // For MySQL, we can use change()
        if (DB::getDriverName() === 'pgsql') {
            Schema::table('sessions', function (Blueprint $table) {
                // Drop indexes that include merchant_id first
                $table->dropIndex(['merchant_id', 'is_active']);
            });
            
            // Drop the column
            DB::statement('ALTER TABLE sessions DROP COLUMN IF EXISTS merchant_id');
            
            // Add it back as string
            Schema::table('sessions', function (Blueprint $table) {
                $table->string('merchant_id')->nullable()->after('updated_by');
                $table->index(['merchant_id', 'is_active']);
            });
        } else {
            // For MySQL/MariaDB
            Schema::table('sessions', function (Blueprint $table) {
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
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropIndex(['merchant_id', 'is_active']);
            });
            
            DB::statement('ALTER TABLE sessions DROP COLUMN IF EXISTS merchant_id');
            
            Schema::table('sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('merchant_id')->nullable()->after('updated_by');
                $table->index(['merchant_id', 'is_active']);
            });
        } else {
            Schema::table('sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('merchant_id')->nullable()->change();
            });
        }
    }
};