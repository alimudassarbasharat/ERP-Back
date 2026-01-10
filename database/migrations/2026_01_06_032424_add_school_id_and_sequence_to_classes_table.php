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
        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'school_id')) {
                $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->onDelete('cascade');
            }
            if (!Schema::hasColumn('classes', 'sequence')) {
                $table->integer('sequence')->nullable()->after('name')->comment('Used for promotion ordering');
            }
        });

        // Add unique constraint and index
        Schema::table('classes', function (Blueprint $table) {
            $table->unique(['school_id', 'name'], 'classes_school_id_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique('classes_school_id_name_unique');
            if (Schema::hasColumn('classes', 'school_id')) {
                $table->dropForeign(['school_id']);
                $table->dropColumn('school_id');
            }
            if (Schema::hasColumn('classes', 'sequence')) {
                $table->dropColumn('sequence');
            }
        });
    }
};
