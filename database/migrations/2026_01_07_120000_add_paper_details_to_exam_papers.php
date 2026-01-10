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
            if (!Schema::hasColumn('exam_papers', 'title')) {
                $table->string('title')->nullable()->after('subject_id');
            }
            if (!Schema::hasColumn('exam_papers', 'instructions')) {
                $table->text('instructions')->nullable()->after('title');
            }
            if (!Schema::hasColumn('exam_papers', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('total_marks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_papers', function (Blueprint $table) {
            $columns = ['title', 'instructions', 'duration_minutes'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('exam_papers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
