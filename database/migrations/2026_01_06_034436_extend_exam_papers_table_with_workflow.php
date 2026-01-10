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
        Schema::table('exam_papers', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_papers', 'school_id')) {
                if (Schema::hasTable('schools')) {
                    $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->onDelete('cascade');
                } else {
                    $table->unsignedBigInteger('school_id')->nullable()->after('id');
                }
            }
            if (!Schema::hasColumn('exam_papers', 'status')) {
                $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'locked'])->default('draft')->after('subject_id');
            }
            if (!Schema::hasColumn('exam_papers', 'paper_version')) {
                $table->integer('paper_version')->default(1)->after('status');
            }
            if (!Schema::hasColumn('exam_papers', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('paper_version')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('exam_papers', 'reviewed_at')) {
                $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (!Schema::hasColumn('exam_papers', 'review_comment')) {
                $table->text('review_comment')->nullable()->after('reviewed_at');
            }
            if (!Schema::hasColumn('exam_papers', 'paper_pdf_path')) {
                $table->string('paper_pdf_path')->nullable()->after('review_comment');
            }
            if (!Schema::hasColumn('exam_papers', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('paper_pdf_path')->constrained('users')->onDelete('set null');
            }
        });

        Schema::table('exam_papers', function (Blueprint $table) {
            $table->index(['school_id', 'status']);
            $table->index(['exam_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_papers', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'status']);
            $table->dropIndex(['exam_id', 'status']);
            
            $columns = ['school_id', 'status', 'paper_version', 'reviewed_by', 'reviewed_at', 
                       'review_comment', 'paper_pdf_path', 'created_by'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('exam_papers', $column)) {
                    if (in_array($column, ['school_id', 'reviewed_by', 'created_by'])) {
                        $table->dropForeign([$column]);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
