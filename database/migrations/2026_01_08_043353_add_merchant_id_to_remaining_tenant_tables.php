<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds merchant_id to all remaining tenant-owned tables that are missing it.
     * This ensures complete multi-tenancy isolation across the ERP system.
     */
    public function up(): void
    {
        // ========================================
        // STEP 1: ADD merchant_id COLUMNS
        // ========================================
        // Initially nullable to avoid breaking existing rows
        
        $tables = [
            'exam_papers',
            'exam_terms',
            'exam_marksheet_configs',
            'exam_questions',
            'class_section',
            'events',
            'mention_notifications',
            'subject_mark_sheets',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'merchant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->string('merchant_id')->nullable()->after('id');
                });
                echo "✓ Added merchant_id to {$table}\n";
            }
        }

        // ========================================
        // STEP 2: BACKFILL EXISTING DATA
        // ========================================
        // Infer merchant_id from parent relationships
        // Execute in correct dependency order
        
        // 2.1: exam_papers (from schools.merchant_id)
        if (Schema::hasTable('exam_papers') && Schema::hasColumn('exam_papers', 'school_id')) {
            DB::statement("
                UPDATE exam_papers ep
                SET merchant_id = s.merchant_id
                FROM schools s
                WHERE ep.school_id = s.id
                AND ep.merchant_id IS NULL
            ");
            echo "✓ Backfilled exam_papers from schools\n";
        }

        // 2.2: exam_terms (from schools.merchant_id)
        if (Schema::hasTable('exam_terms') && Schema::hasColumn('exam_terms', 'school_id')) {
            DB::statement("
                UPDATE exam_terms et
                SET merchant_id = s.merchant_id
                FROM schools s
                WHERE et.school_id = s.id
                AND et.merchant_id IS NULL
            ");
            echo "✓ Backfilled exam_terms from schools\n";
        }

        // 2.3: exam_marksheet_configs (from schools.merchant_id)
        if (Schema::hasTable('exam_marksheet_configs') && Schema::hasColumn('exam_marksheet_configs', 'school_id')) {
            DB::statement("
                UPDATE exam_marksheet_configs emc
                SET merchant_id = s.merchant_id
                FROM schools s
                WHERE emc.school_id = s.id
                AND emc.merchant_id IS NULL
            ");
            echo "✓ Backfilled exam_marksheet_configs from schools\n";
        }

        // 2.4: exam_questions (from exam_papers.merchant_id - MUST run after exam_papers backfill)
        if (Schema::hasTable('exam_questions')) {
            DB::statement("
                UPDATE exam_questions eq
                SET merchant_id = ep.merchant_id
                FROM exam_papers ep
                WHERE eq.exam_paper_id = ep.id
                AND eq.merchant_id IS NULL
            ");
            echo "✓ Backfilled exam_questions from exam_papers\n";
        }

        // 2.5: class_section (from classes.merchant_id)
        if (Schema::hasTable('class_section')) {
            DB::statement("
                UPDATE class_section cs
                SET merchant_id = c.merchant_id
                FROM classes c
                WHERE cs.class_id = c.id
                AND cs.merchant_id IS NULL
            ");
            echo "✓ Backfilled class_section from classes\n";
        }

        // 2.6: events (from users.merchant_id)
        if (Schema::hasTable('events')) {
            DB::statement("
                UPDATE events e
                SET merchant_id = u.merchant_id
                FROM users u
                WHERE e.user_id = u.id
                AND e.merchant_id IS NULL
            ");
            echo "✓ Backfilled events from users\n";
        }

        // 2.7: mention_notifications (from users.merchant_id)
        if (Schema::hasTable('mention_notifications')) {
            DB::statement("
                UPDATE mention_notifications mn
                SET merchant_id = u.merchant_id
                FROM users u
                WHERE mn.user_id = u.id
                AND mn.merchant_id IS NULL
            ");
            echo "✓ Backfilled mention_notifications from users\n";
        }

        // 2.8: subject_mark_sheets (from students.merchant_id)
        if (Schema::hasTable('subject_mark_sheets')) {
            DB::statement("
                UPDATE subject_mark_sheets sms
                SET merchant_id = s.merchant_id
                FROM students s
                WHERE sms.student_id = s.id
                AND sms.merchant_id IS NULL
            ");
            echo "✓ Backfilled subject_mark_sheets from students\n";
        }

        // ========================================
        // STEP 3: MAKE merchant_id NOT NULL
        // ========================================
        // Now that all existing rows have merchant_id, make it required
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'merchant_id')) {
                // Check if there are any null values left
                $nullCount = DB::table($table)->whereNull('merchant_id')->count();
                
                if ($nullCount > 0) {
                    echo "⚠ WARNING: {$table} still has {$nullCount} rows with NULL merchant_id. Skipping NOT NULL constraint.\n";
                    continue;
                }

                Schema::table($table, function (Blueprint $table) {
                    $table->string('merchant_id')->nullable(false)->change();
                });
                echo "✓ Made merchant_id NOT NULL in {$table}\n";
            }
        }

        // ========================================
        // STEP 4: ADD INDEXES
        // ========================================
        // Single column index for all tables
        
        $indexConfigs = [
            'exam_papers' => [
                ['merchant_id'],
                ['merchant_id', 'school_id'],
                ['merchant_id', 'status'],
                ['merchant_id', 'exam_date'],
            ],
            'exam_terms' => [
                ['merchant_id'],
                ['merchant_id', 'school_id'],
                ['merchant_id', 'session_id'],
                ['merchant_id', 'status'],
            ],
            'exam_marksheet_configs' => [
                ['merchant_id'],
                ['merchant_id', 'school_id'],
                ['merchant_id', 'exam_id'],
            ],
            'exam_questions' => [
                ['merchant_id'],
                ['merchant_id', 'exam_paper_id'],
            ],
            'class_section' => [
                ['merchant_id'],
                ['merchant_id', 'class_id'],
                ['merchant_id', 'section_id'],
            ],
            'events' => [
                ['merchant_id'],
                ['merchant_id', 'user_id'],
                ['merchant_id', 'type'],
                ['merchant_id', 'start_date'],
            ],
            'mention_notifications' => [
                ['merchant_id'],
                ['merchant_id', 'user_id'],
                ['merchant_id', 'is_read'],
            ],
            'subject_mark_sheets' => [
                ['merchant_id'],
                ['merchant_id', 'student_id'],
                ['merchant_id', 'exam_id'],
                ['merchant_id', 'status'],
            ],
        ];

        foreach ($indexConfigs as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $columns) {
                $indexName = $table . '_' . implode('_', $columns) . '_index';
                
                // Check if index already exists
                $indexExists = DB::select("
                    SELECT 1
                    FROM pg_indexes
                    WHERE tablename = ?
                    AND indexname = ?
                ", [$table, $indexName]);

                if (empty($indexExists)) {
                    Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                        $table->index($columns, $indexName);
                    });
                    echo "✓ Added index {$indexName}\n";
                }
            }
        }

        // ========================================
        // STEP 5: ADD FOREIGN KEY CONSTRAINTS (Optional, for data integrity)
        // ========================================
        // Note: Only add if you have a merchants table
        // Commented out for now as we're using string merchant_id

        /*
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasTable('merchants')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreign('merchant_id')
                          ->references('id')
                          ->on('merchants')
                          ->onDelete('cascade');
                });
            }
        }
        */

        echo "\n✅ Migration completed successfully!\n";
        echo "✅ All 8 tables now have merchant_id with proper indexes.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'exam_papers',
            'exam_terms',
            'exam_marksheet_configs',
            'exam_questions',
            'class_section',
            'events',
            'mention_notifications',
            'subject_mark_sheets',
        ];

        // Drop indexes first
        $indexConfigs = [
            'exam_papers' => [
                ['merchant_id'],
                ['merchant_id', 'school_id'],
                ['merchant_id', 'status'],
                ['merchant_id', 'exam_date'],
            ],
            'exam_terms' => [
                ['merchant_id'],
                ['merchant_id', 'school_id'],
                ['merchant_id', 'session_id'],
                ['merchant_id', 'status'],
            ],
            'exam_marksheet_configs' => [
                ['merchant_id'],
                ['merchant_id', 'school_id'],
                ['merchant_id', 'exam_id'],
            ],
            'exam_questions' => [
                ['merchant_id'],
                ['merchant_id', 'exam_paper_id'],
            ],
            'class_section' => [
                ['merchant_id'],
                ['merchant_id', 'class_id'],
                ['merchant_id', 'section_id'],
            ],
            'events' => [
                ['merchant_id'],
                ['merchant_id', 'user_id'],
                ['merchant_id', 'type'],
                ['merchant_id', 'start_date'],
            ],
            'mention_notifications' => [
                ['merchant_id'],
                ['merchant_id', 'user_id'],
                ['merchant_id', 'is_read'],
            ],
            'subject_mark_sheets' => [
                ['merchant_id'],
                ['merchant_id', 'student_id'],
                ['merchant_id', 'exam_id'],
                ['merchant_id', 'status'],
            ],
        ];

        foreach ($indexConfigs as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $columns) {
                $indexName = $table . '_' . implode('_', $columns) . '_index';
                
                Schema::table($table, function (Blueprint $table) use ($indexName) {
                    try {
                        $table->dropIndex($indexName);
                    } catch (\Exception $e) {
                        // Index might not exist, continue
                    }
                });
            }
        }

        // Drop columns
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'merchant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('merchant_id');
                });
            }
        }
    }
};
