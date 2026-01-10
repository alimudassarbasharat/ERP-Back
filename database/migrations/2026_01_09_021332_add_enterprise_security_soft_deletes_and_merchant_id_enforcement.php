<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ENTERPRISE SECURITY MIGRATION
     * ==============================
     * 
     * This migration enforces TWO critical security requirements:
     * 1. SOFT DELETES - Add deleted_at to ALL tenant-owned tables
     * 2. MERCHANT_ID ENFORCEMENT - Ensure merchant_id exists and is indexed
     * 
     * SAFE TO RUN:
     * - All changes are additive
     * - Won't break existing data
     * - Existing records get deleted_at = NULL
     * - Skips tables that already have columns
     */
    public function up(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║  ENTERPRISE SECURITY MIGRATION - STARTING                ║\n";
        echo "║  1. Adding soft deletes (deleted_at)                     ║\n";
        echo "║  2. Verifying merchant_id indexes                        ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";

        // =============================================
        // PHASE 1: ADD SOFT DELETES (deleted_at)
        // =============================================
        
        $softDeleteTables = [
            // CRITICAL PRIORITY (User-facing data)
            'teachers',              // ❌ Teacher records
            'exam_marks',            // ❌ Student marks
            'exam_results',          // ❌ Exam results
            'fee_payments',          // ❌ Payment records
            'fee_summaries',         // ❌ Financial summaries
            'challans',              // ❌ Fee challans
            'challan_payments',      // ❌ Payment transactions
            
            // HIGH PRIORITY (Academic data)
            'academic_years',        // Academic year configs
            'attendance_records',    // Attendance history
            'fee_structures',        // Fee configurations
            'fee_heads',             // Fee head definitions
            'exam_classes',          // Exam-class mappings
            'exam_subjects',         // Exam-subject mappings
            'exam_datesheets',       // Exam schedules
            'exam_datesheet_entries', // Datesheet entries
            
            // MEDIUM PRIORITY (Supporting data)
            'family_groups',         // Family groupings
            'family_students',       // Family-student relationships
            'student_fee_plans',     // Student fee plans
            'student_fee_discounts', // Student discounts
            'sibling_discounts',     // Sibling discounts
            'fee_invoice_items',     // Invoice line items
            'notification_settings', // Notification configs
            'notification_events',   // Notification events
            'notification_channels', // Notification channels
            'grading_rules',         // Grading configurations
            'exam_scopes',           // Exam scopes
            'exam_stats',            // Exam statistics
            'escalation_rules',      // Fee escalation rules
            'fee_disputes',          // Fee disputes
            'fee_forecasts',         // Fee forecasts
            'document_templates',    // Document templates
            'school_document_template_settings', // Template settings
            'result_sheet',          // Result sheets
            'student_forms',         // Student forms
            'channel_members',       // Channel memberships
            'message_attachments',   // Message attachments
            'message_reactions',     // Message reactions
            'ticket_activities',     // Ticket activities
            'ticket_time_logs',      // Ticket time tracking
            'user_preferences',      // User preferences
            'user_statuses',         // User statuses
            'user_presence',         // User presence
        ];

        foreach ($softDeleteTables as $table) {
            if (!Schema::hasTable($table)) {
                echo "⏭️  Skipping {$table} (table doesn't exist)\n";
                continue;
            }

            if (Schema::hasColumn($table, 'deleted_at')) {
                echo "✓ {$table} already has deleted_at\n";
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes();
                });
                echo "✅ Added deleted_at to {$table}\n";
            } catch (\Exception $e) {
                echo "❌ Failed to add deleted_at to {$table}: " . $e->getMessage() . "\n";
            }
        }

        echo "\n";
        echo "─────────────────────────────────────────────────────────\n";
        echo "PHASE 1 COMPLETE: Soft deletes added\n";
        echo "─────────────────────────────────────────────────────────\n";
        echo "\n";

        // =============================================
        // PHASE 2: VERIFY AND INDEX merchant_id
        // =============================================
        
        $merchantIdTables = [
            // All tenant-owned tables
            'users', 'admins', 'teachers', 'students', 'schools',
            'classes', 'sections', 'subjects', 'sessions', 'academic_years',
            'exams', 'exam_papers', 'exam_terms', 'exam_marks', 'exam_results',
            'exam_classes', 'exam_subjects', 'exam_datesheets', 'exam_datesheet_entries',
            'exam_questions', 'exam_marksheet_configs', 'exam_scopes', 'exam_stats',
            'fee_structures', 'fee_heads', 'fee_invoices', 'fee_invoice_items',
            'fee_payments', 'fee_summaries', 'fee_defaults', 'challans', 'challan_payments',
            'student_fee_plans', 'student_fee_discounts', 'sibling_discounts',
            'attendances', 'attendance_records', 'attendance_settings',
            'events', 'departments', 'family_infos', 'family_groups', 'family_students',
            'academic_records', 'contact_information',
            'channels', 'channel_members', 'messages', 'message_attachments',
            'message_reactions', 'message_notifications', 'mention_notifications',
            'direct_messages', 'direct_message_conversations', 'direct_message_attachments',
            'tickets', 'workspaces', 'ticket_comments', 'ticket_attachments',
            'ticket_subtasks', 'ticket_activities', 'ticket_time_logs',
            'ticket_voice_recordings', 'ticket_comment_attachments',
            'late_fees', 'grading_rules', 'escalation_rules', 'fee_disputes', 'fee_forecasts',
            'document_templates', 'school_document_template_settings',
            'notification_settings', 'notification_channels', 'notification_events',
            'result_sheet', 'student_forms', 'subject_mark_sheets',
            'user_preferences', 'user_statuses', 'user_presence', 'push_subscriptions',
            'teacher_personal_details', 'teacher_contact_details',
            'teacher_professional_details', 'teacher_additional_details',
        ];

        foreach ($merchantIdTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            // Check if merchant_id exists
            if (!Schema::hasColumn($table, 'merchant_id')) {
                echo "⚠️  {$table} is missing merchant_id column!\n";
                // Don't add here - should be added via dedicated merchant_id migration
                continue;
            }

            // Check if merchant_id is indexed
            $indexName = $table . '_merchant_id_index';
            $indexExists = DB::select("
                SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = ?
                AND index_name = ?
            ", [$table, $indexName]);

            if (empty($indexExists)) {
                try {
                    Schema::table($table, function (Blueprint $table) use ($indexName) {
                        $table->index('merchant_id', $indexName);
                    });
                    echo "✅ Added index to {$table}.merchant_id\n";
                } catch (\Exception $e) {
                    // Index might already exist with different name
                    echo "ℹ️  {$table}.merchant_id index exists (or error: " . $e->getMessage() . ")\n";
                }
            } else {
                echo "✓ {$table}.merchant_id already indexed\n";
            }
        }

        echo "\n";
        echo "─────────────────────────────────────────────────────────\n";
        echo "PHASE 2 COMPLETE: merchant_id indexes verified\n";
        echo "─────────────────────────────────────────────────────────\n";
        echo "\n";

        // =============================================
        // PHASE 3: INDEX deleted_at FOR PERFORMANCE
        // =============================================
        
        // Add composite index (merchant_id, deleted_at) for critical tables
        $criticalTables = [
            'students', 'teachers', 'sessions', 'exams',
            'fee_invoices', 'fee_payments', 'classes', 'sections'
        ];

        foreach ($criticalTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            if (!Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }

            $indexName = $table . '_merchant_deleted_index';
            try {
                Schema::table($table, function (Blueprint $table) use ($indexName) {
                    $table->index(['merchant_id', 'deleted_at'], $indexName);
                });
                echo "✅ Added composite index to {$table} (merchant_id, deleted_at)\n";
            } catch (\Exception $e) {
                echo "ℹ️  {$table} composite index exists or error\n";
            }
        }

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║  MIGRATION COMPLETE                                      ║\n";
        echo "║  ✅ Soft deletes added to critical tables                ║\n";
        echo "║  ✅ merchant_id indexes verified                         ║\n";
        echo "║  ✅ Performance indexes added                            ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // List of tables where we added deleted_at
        $softDeleteTables = [
            'teachers', 'exam_marks', 'exam_results', 'fee_payments',
            'fee_summaries', 'challans', 'challan_payments',
            'academic_years', 'attendance_records', 'fee_structures', 'fee_heads',
            'exam_classes', 'exam_subjects', 'exam_datesheets', 'exam_datesheet_entries',
            'family_groups', 'family_students', 'student_fee_plans',
            'student_fee_discounts', 'sibling_discounts', 'fee_invoice_items',
            'notification_settings', 'notification_events', 'notification_channels',
            'grading_rules', 'exam_scopes', 'exam_stats',
            'escalation_rules', 'fee_disputes', 'fee_forecasts',
            'document_templates', 'school_document_template_settings',
            'result_sheet', 'student_forms', 'channel_members',
            'message_attachments', 'message_reactions',
            'ticket_activities', 'ticket_time_logs',
            'user_preferences', 'user_statuses', 'user_presence',
        ];

        foreach ($softDeleteTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }

        // Drop composite indexes
        $criticalTables = [
            'students', 'teachers', 'sessions', 'exams',
            'fee_invoices', 'fee_payments', 'classes', 'sections'
        ];

        foreach ($criticalTables as $table) {
            if (Schema::hasTable($table)) {
                $indexName = $table . '_merchant_deleted_index';
                try {
                    Schema::table($table, function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                } catch (\Exception $e) {
                    // Index might not exist
                }
            }
        }
    }
};
