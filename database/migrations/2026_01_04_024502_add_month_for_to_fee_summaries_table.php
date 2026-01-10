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
        Schema::table('fee_summaries', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_summaries', 'month_for')) {
                $table->date('month_for')->nullable()->after('student_id');
                $table->index('month_for');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_summaries', function (Blueprint $table) {
            if (Schema::hasColumn('fee_summaries', 'month_for')) {
                $table->dropIndex(['month_for']);
                $table->dropColumn('month_for');
            }
        });
    }
};
