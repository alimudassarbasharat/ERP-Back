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
        Schema::table('sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('sessions', 'school_id')) {
                $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->onDelete('cascade');
            }
            if (!Schema::hasColumn('sessions', 'start_date')) {
                $table->date('start_date')->nullable()->after('name');
            }
            if (!Schema::hasColumn('sessions', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('sessions', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('end_date');
            }
        });

        // Add indexes
        Schema::table('sessions', function (Blueprint $table) {
            $table->index(['school_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'is_active']);
            $table->dropForeign(['school_id']);
            $table->dropColumn(['school_id', 'start_date', 'end_date', 'is_active']);
        });
    }
};
