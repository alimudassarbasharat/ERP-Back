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
        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'school_id')) {
                $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->onDelete('cascade');
            }
            if (!Schema::hasColumn('exams', 'session_id')) {
                $table->foreignId('session_id')->nullable()->after('school_id')->constrained('sessions')->onDelete('cascade');
            }
            if (!Schema::hasColumn('exams', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (!Schema::hasColumn('exams', 'end_date')) {
                $table->date('end_date')->nullable();
            }
            if (!Schema::hasColumn('exams', 'status')) {
                $table->enum('status', ['draft', 'running', 'locked', 'published'])->default('draft');
            } else {
                // Update existing status if needed
                $table->enum('status', ['draft', 'running', 'locked', 'published'])->default('draft')->change();
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->index(['school_id', 'session_id', 'status'], 'exams_school_session_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex('exams_school_session_status_idx');
            if (Schema::hasColumn('exams', 'school_id')) {
                $table->dropForeign(['school_id']);
                $table->dropColumn('school_id');
            }
            if (Schema::hasColumn('exams', 'session_id')) {
                $table->dropForeign(['session_id']);
                $table->dropColumn('session_id');
            }
            if (Schema::hasColumn('exams', 'start_date')) {
                $table->dropColumn('start_date');
            }
            if (Schema::hasColumn('exams', 'end_date')) {
                $table->dropColumn('end_date');
            }
        });
    }
};
