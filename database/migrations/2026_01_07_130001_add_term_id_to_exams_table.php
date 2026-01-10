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
            if (!Schema::hasColumn('exams', 'term_id')) {
                $table->foreignId('term_id')->nullable()->after('session_id')->constrained('exam_terms')->onDelete('restrict');
                $table->index('term_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'term_id')) {
                $table->dropForeign(['term_id']);
                $table->dropIndex(['term_id', 'status']);
                $table->dropColumn('term_id');
            }
        });
    }
};
