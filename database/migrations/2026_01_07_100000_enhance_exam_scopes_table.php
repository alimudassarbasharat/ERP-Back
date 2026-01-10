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
        Schema::table('exam_scopes', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_scopes', 'exam_id')) {
                $table->foreignId('exam_id')->after('id')->constrained('exams')->onDelete('cascade');
            }
            if (!Schema::hasColumn('exam_scopes', 'school_id')) {
                $table->foreignId('school_id')->after('exam_id')->nullable()->constrained('schools')->onDelete('cascade');
            }
            if (!Schema::hasColumn('exam_scopes', 'scope_type')) {
                $table->enum('scope_type', ['class', 'section', 'student', 'family'])->default('class')->after('school_id');
            }
            if (!Schema::hasColumn('exam_scopes', 'class_id')) {
                $table->foreignId('class_id')->nullable()->after('scope_type')->constrained('classes')->onDelete('cascade');
            }
            if (!Schema::hasColumn('exam_scopes', 'section_id')) {
                $table->foreignId('section_id')->nullable()->after('class_id')->constrained('sections')->onDelete('cascade');
            }
            if (!Schema::hasColumn('exam_scopes', 'student_id')) {
                $table->foreignId('student_id')->nullable()->after('section_id')->constrained('students')->onDelete('cascade');
            }
            if (!Schema::hasColumn('exam_scopes', 'family_group_id')) {
                $table->string('family_group_id')->nullable()->after('student_id');
            }
        });

        Schema::table('exam_scopes', function (Blueprint $table) {
            $table->index(['exam_id', 'scope_type']);
            $table->index(['school_id', 'class_id', 'section_id']);
            $table->unique(['exam_id', 'class_id', 'section_id', 'student_id'], 'exam_scopes_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_scopes', function (Blueprint $table) {
            $table->dropUnique('exam_scopes_unique');
            $table->dropIndex(['exam_id', 'scope_type']);
            $table->dropIndex(['school_id', 'class_id', 'section_id']);
            
            $columns = ['exam_id', 'school_id', 'scope_type', 'class_id', 'section_id', 'student_id', 'family_group_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('exam_scopes', $column)) {
                    if (in_array($column, ['exam_id', 'school_id', 'class_id', 'section_id', 'student_id'])) {
                        $table->dropForeign([$column]);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
