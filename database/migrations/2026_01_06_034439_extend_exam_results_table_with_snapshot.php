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
        Schema::table('exam_results', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_results', 'rank_in_class')) {
                $table->integer('rank_in_class')->nullable()->after('grade');
            }
            if (!Schema::hasColumn('exam_results', 'result_snapshot_json')) {
                $table->json('result_snapshot_json')->nullable()->after('rank_in_class')->comment('Full snapshot of marks breakdown');
            }
            if (!Schema::hasColumn('exam_results', 'marksheet_pdf_path')) {
                $table->string('marksheet_pdf_path')->nullable()->after('result_snapshot_json');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $columns = ['rank_in_class', 'result_snapshot_json', 'marksheet_pdf_path'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('exam_results', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
