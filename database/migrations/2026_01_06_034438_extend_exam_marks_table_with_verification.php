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
        Schema::table('exam_marks', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_marks', 'status')) {
                $table->enum('status', ['draft', 'submitted', 'verified', 'locked'])->default('draft')->after('is_absent');
            }
            if (!Schema::hasColumn('exam_marks', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('status')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('exam_marks', 'verified_at')) {
                $table->dateTime('verified_at')->nullable()->after('verified_by');
            }
            if (!Schema::hasColumn('exam_marks', 'submitted_at')) {
                $table->dateTime('submitted_at')->nullable()->after('verified_at');
            }
        });

        Schema::table('exam_marks', function (Blueprint $table) {
            $table->index(['exam_id', 'status']);
            $table->index(['class_id', 'subject_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_marks', function (Blueprint $table) {
            $table->dropIndex(['exam_id', 'status']);
            $table->dropIndex(['class_id', 'subject_id', 'status']);
            
            $columns = ['status', 'verified_by', 'verified_at', 'submitted_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('exam_marks', $column)) {
                    if ($column === 'verified_by') {
                        $table->dropForeign(['verified_by']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
