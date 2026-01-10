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
        Schema::table('message_notifications', function (Blueprint $table) {
            // CRITICAL: Add index for tenant scoping if it doesn't exist
            if (!$this->indexExists('message_notifications', 'message_notifications_merchant_id_index')) {
                $table->index('merchant_id', 'message_notifications_merchant_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_notifications', function (Blueprint $table) {
            $table->dropIndex('message_notifications_merchant_id_index');
        });
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        if ($connection->getDriverName() === 'pgsql') {
            $result = $connection->selectOne(
                "SELECT COUNT(*) as count FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $indexName]
            );
            return $result->count > 0;
        }
        
        // For MySQL
        $result = $connection->selectOne(
            "SELECT COUNT(*) as count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        return $result->count > 0;
    }
};
