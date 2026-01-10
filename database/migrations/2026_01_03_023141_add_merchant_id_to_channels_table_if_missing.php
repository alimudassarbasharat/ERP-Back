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
        // Add merchant_id to channels table if it doesn't exist
        if (Schema::hasTable('channels') && !Schema::hasColumn('channels', 'merchant_id')) {
            Schema::table('channels', function (Blueprint $table) {
                $table->string('merchant_id')->nullable()->after('id');
                $table->index('merchant_id');
            });

            // Backfill merchant_id from users who created channels
            // Get merchant_id from created_by user
            DB::statement("
                UPDATE channels c
                SET merchant_id = (
                    SELECT COALESCE(u.merchant_id, a.merchant_id, 'DEFAULT_TENANT')
                    FROM users u
                    LEFT JOIN admins a ON u.id = a.user_id
                    WHERE u.id = c.created_by
                    LIMIT 1
                )
                WHERE c.merchant_id IS NULL
            ");

            // For channels without created_by, use merchant_id from first member
            DB::statement("
                UPDATE channels c
                SET merchant_id = (
                    SELECT COALESCE(u.merchant_id, 'DEFAULT_TENANT')
                    FROM channel_users cu
                    JOIN users u ON u.id = cu.user_id
                    WHERE cu.channel_id = c.id
                    LIMIT 1
                )
                WHERE c.merchant_id IS NULL
            ");

            // Make merchant_id required after backfill
            Schema::table('channels', function (Blueprint $table) {
                $table->string('merchant_id')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('channels') && Schema::hasColumn('channels', 'merchant_id')) {
            Schema::table('channels', function (Blueprint $table) {
                $table->dropIndex(['merchant_id']);
                $table->dropColumn('merchant_id');
            });
        }
    }
};
