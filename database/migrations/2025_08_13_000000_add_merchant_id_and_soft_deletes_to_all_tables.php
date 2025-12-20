<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            // Core/user-related
            'users', 'admins', 'user_preferences', 'user_statuses',

            // Academic entities
            'students', 'classes', 'sections', 'subjects', 'exams', 'academic_years',
            'attendance_records', 'attendance_settings', 'attendances', 'result_sheet',

            // Teacher and related details
            'teachers', 'teacher_personal_details', 'teacher_contact_details',
            'teacher_professional_details', 'teacher_additional_details',

            // Relationships/pivots
            'class_subject', 'class_subjects', 'teacher_subjects', 'teacher_classes', 'exam_subject',

            // Fees/finance
            'late_fees', 'fee_defaults', 'fee_summaries', 'fee_payments',

            // Comms/messaging
            'channels', 'channel_members', 'channel_users', 'messages', 'message_reactions',
            'message_attachments', 'direct_message_conversations', 'direct_message_participants',
            'direct_messages', 'direct_message_attachments', 'message_read_receipts',
            'direct_message_read_receipts', 'user_presence', 'typing_indicators', 'sessions',

            // Misc
            'departments', 'family_infos', 'academic_records', 'contact_information', 'country_codes',

            // Spatie permission (and simplified variants)
            'roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions',

            // OAuth / Laravel tables (include for consistency; guarded by hasColumn checks)
            'oauth_personal_access_clients', 'oauth_clients', 'oauth_refresh_tokens', 'oauth_access_tokens',
            'oauth_auth_codes', 'personal_access_tokens', 'password_resets', 'failed_jobs',

            // Custom naming from report cards migration
            'student_forms',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'merchant_id')) {
                    $table->string('merchant_id')->nullable();
                }
                if (!Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'users', 'admins', 'user_preferences', 'user_statuses',
            'students', 'classes', 'sections', 'subjects', 'exams', 'academic_years',
            'attendance_records', 'attendance_settings', 'attendances', 'result_sheet',
            'teachers', 'teacher_personal_details', 'teacher_contact_details',
            'teacher_professional_details', 'teacher_additional_details',
            'class_subject', 'class_subjects', 'teacher_subjects', 'teacher_classes', 'exam_subject',
            'late_fees', 'fee_defaults', 'fee_summaries', 'fee_payments',
            'channels', 'channel_members', 'channel_users', 'messages', 'message_reactions',
            'message_attachments', 'direct_message_conversations', 'direct_message_participants',
            'direct_messages', 'direct_message_attachments', 'message_read_receipts',
            'direct_message_read_receipts', 'user_presence', 'typing_indicators', 'sessions',
            'departments', 'family_infos', 'academic_records', 'contact_information', 'country_codes',
            'roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions',
            'oauth_personal_access_clients', 'oauth_clients', 'oauth_refresh_tokens', 'oauth_access_tokens',
            'oauth_auth_codes', 'personal_access_tokens', 'password_resets', 'failed_jobs',
            'student_forms',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'merchant_id')) {
                    try { $table->dropColumn('merchant_id'); } catch (\Throwable $e) {}
                }
                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    try { $table->dropColumn('deleted_at'); } catch (\Throwable $e) {}
                }
            });
        }
    }
};


