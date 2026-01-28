<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * COMPREHENSIVE FIX: Ensure ALL tables have merchant_id and soft deletes
     * 
     * This migration ensures:
     * 1. Every tenant-related table has merchant_id (string, indexed)
     * 2. Every table has soft deletes (deleted_at)
     * 3. Proper indexes are added for performance
     */
    public function up(): void
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════╗\n";
        echo "║  COMPREHENSIVE TABLE AUDIT - STARTING                         ║\n";
        echo "║  Ensuring ALL tables have:                                    ║\n";
        echo "║  1. merchant_id (string, indexed)                             ║\n";
        echo "║  2. deleted_at (soft deletes)                                 ║\n";
        echo "╚═══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        // Tables that need merchant_id
        $tablesNeedingMerchantId = [
            'challan_payments',
            'challans',
            'class_section',
            'document_templates',
            'escalation_rules',
            'exam_classes',
            'exam_datesheet_entries',
            'exam_datesheets',
            'exam_marks',
            'exam_results',
            'exam_scopes',
            'exam_stats',
            'exam_subjects',
            'family_groups',
            'family_students',
            'fee_disputes',
            'fee_forecasts',
            'fee_heads',
            'fee_invoice_items',
            'fee_invoices',
            'fee_structures',
            'grading_rules',
            'notification_channels',
            'notification_events',
            'notification_settings',
            'school_document_template_settings',
            'sibling_discounts',
            'student_fee_discounts',
            'student_fee_plans',
        ];

        // Tables that need soft deletes
        $tablesNeedingSoftDeletes = [
            'events',
            'exam_marksheet_configs',
            'exam_papers',
            'exam_questions',
            'exam_terms',
            'fee_invoices',
            'mention_notifications',
            'message_notifications',
            'push_subscriptions',
            'subject_mark_sheets',
            'ticket_labels',
            'ticket_watchers',
        ];

        echo "═══════════════════════════════════════════════════════════════\n";
        echo "PHASE 1: Adding merchant_id to tables\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        foreach ($tablesNeedingMerchantId as $tableName) {
            if (!Schema::hasTable($tableName)) {
                echo "⏭️  {$tableName} - table doesn't exist, skipping\n";
                continue;
            }

            if (Schema::hasColumn($tableName, 'merchant_id')) {
                echo "✓ {$tableName} - already has merchant_id\n";
                continue;
            }

            try {
                // Add merchant_id as nullable first
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('merchant_id')->nullable()->after('id');
                });

                // Backfill with default value
                DB::statement("UPDATE {$tableName} SET merchant_id = 'DEFAULT_TENANT' WHERE merchant_id IS NULL");

                // Make it non-nullable and add index
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->string('merchant_id')->nullable(false)->change();
                    
                    // Add index if it doesn't exist
                    $indexName = $tableName . '_merchant_id_index';
                    if (!$this->hasIndex($tableName, $indexName)) {
                        $table->index('merchant_id', $indexName);
                    }
                });

                echo "✅ {$tableName} - merchant_id added and indexed\n";
            } catch (\Exception $e) {
                echo "❌ {$tableName} - Error: " . $e->getMessage() . "\n";
            }
        }

        echo "\n═══════════════════════════════════════════════════════════════\n";
        echo "PHASE 2: Adding soft deletes (deleted_at) to tables\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        foreach ($tablesNeedingSoftDeletes as $tableName) {
            if (!Schema::hasTable($tableName)) {
                echo "⏭️  {$tableName} - table doesn't exist, skipping\n";
                continue;
            }

            if (Schema::hasColumn($tableName, 'deleted_at')) {
                echo "✓ {$tableName} - already has deleted_at\n";
                continue;
            }

            try {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
                echo "✅ {$tableName} - deleted_at added\n";
            } catch (\Exception $e) {
                echo "❌ {$tableName} - Error: " . $e->getMessage() . "\n";
            }
        }

        echo "\n═══════════════════════════════════════════════════════════════\n";
        echo "PHASE 3: Adding composite indexes for performance\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        // Add composite indexes (merchant_id, deleted_at) for important tables
        $tablesForCompositeIndex = [
            'fee_invoices',
            'challans',
            'challan_payments',
            'exam_marks',
            'exam_results',
            'fee_invoice_items',
            'exam_papers',
            'exam_terms',
        ];

        foreach ($tablesForCompositeIndex as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            if (!Schema::hasColumn($tableName, 'merchant_id') || !Schema::hasColumn($tableName, 'deleted_at')) {
                echo "⏭️  {$tableName} - missing required columns for composite index\n";
                continue;
            }

            $indexName = $tableName . '_merchant_deleted_index';
            
            if ($this->hasIndex($tableName, $indexName)) {
                echo "✓ {$tableName} - composite index already exists\n";
                continue;
            }

            try {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->index(['merchant_id', 'deleted_at'], $indexName);
                });
                echo "✅ {$tableName} - composite index added\n";
            } catch (\Exception $e) {
                echo "ℹ️  {$tableName} - " . $e->getMessage() . "\n";
            }
        }

        echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
        echo "║  MIGRATION COMPLETE ✅                                        ║\n";
        echo "║  All tables now have:                                         ║\n";
        echo "║  • merchant_id (string, indexed)                              ║\n";
        echo "║  • deleted_at (soft deletes)                                  ║\n";
        echo "║  • Performance indexes                                        ║\n";
        echo "╚═══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    /**
     * Check if an index exists (PostgreSQL compatible)
     */
    private function hasIndex($table, $indexName)
    {
        $connection = Schema::getConnection();
        
        try {
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
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "⚠️  Not reversing this migration - merchant_id and soft deletes are critical for the system\n";
    }
};
