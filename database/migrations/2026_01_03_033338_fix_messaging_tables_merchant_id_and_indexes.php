<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix channel_users table - ensure merchant_id exists
        if (Schema::hasTable('channel_users') && !Schema::hasColumn('channel_users', 'merchant_id')) {
            Schema::table('channel_users', function (Blueprint $table) {
                $table->string('merchant_id')->nullable()->after('id');
                $table->index('merchant_id');
            });
            
            // Backfill merchant_id from channels table
            DB::statement("
                UPDATE channel_users cu
                SET merchant_id = c.merchant_id
                FROM channels c
                WHERE cu.channel_id = c.id
            ");
        }

        // Fix messages table - ensure merchant_id exists
        if (Schema::hasTable('messages') && !Schema::hasColumn('messages', 'merchant_id')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->string('merchant_id')->nullable()->after('id');
                $table->index('merchant_id');
            });
            
            // Backfill from channels
            DB::statement("
                UPDATE messages m
                SET merchant_id = c.merchant_id
                FROM channels c
                WHERE m.channel_id = c.id
            ");
        }

        // Fix direct_messages table - ensure merchant_id exists
        if (Schema::hasTable('direct_messages') && !Schema::hasColumn('direct_messages', 'merchant_id')) {
            Schema::table('direct_messages', function (Blueprint $table) {
                $table->string('merchant_id')->nullable()->after('id');
                $table->index('merchant_id');
            });
            
            // Backfill from conversations
            DB::statement("
                UPDATE direct_messages dm
                SET merchant_id = dmc.merchant_id
                FROM direct_message_conversations dmc
                WHERE dm.conversation_id = dmc.id
            ");
        }

        // Fix direct_message_participants - ensure merchant_id exists
        if (Schema::hasTable('direct_message_participants') && !Schema::hasColumn('direct_message_participants', 'merchant_id')) {
            Schema::table('direct_message_participants', function (Blueprint $table) {
                $table->string('merchant_id')->nullable()->after('id');
                $table->index('merchant_id');
            });
            
            // Backfill from conversations
            DB::statement("
                UPDATE direct_message_participants dmp
                SET merchant_id = dmc.merchant_id
                FROM direct_message_conversations dmc
                WHERE dmp.conversation_id = dmc.id
            ");
        }

        // Add missing indexes for performance
        if (Schema::hasTable('channel_users')) {
            Schema::table('channel_users', function (Blueprint $table) {
                if (!$this->hasIndex('channel_users', 'channel_users_user_id_index')) {
                    $table->index('user_id');
                }
                if (!$this->hasIndex('channel_users', 'channel_users_channel_id_index')) {
                    $table->index('channel_id');
                }
            });
        }
    }

    private function hasIndex($table, $indexName)
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        if ($driver === 'pgsql') {
            $result = DB::select(
                "SELECT COUNT(*) as count FROM pg_indexes WHERE schemaname = 'public' AND tablename = ? AND indexname = ?",
                [$table, $indexName]
            );
            return $result[0]->count > 0;
        }
        
        // MySQL fallback
        $databaseName = $connection->getDatabaseName();
        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        return $result[0]->count > 0;
    }

    public function down(): void
    {
        // Don't remove merchant_id columns as they're critical for tenant isolation
    }
};
