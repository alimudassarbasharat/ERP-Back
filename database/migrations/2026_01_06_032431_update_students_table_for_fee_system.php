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
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'school_id')) {
                $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->onDelete('cascade');
            }
            if (!Schema::hasColumn('students', 'admission_no')) {
                $table->string('admission_no')->nullable()->after('school_id');
            }
            if (!Schema::hasColumn('students', 'name')) {
                // Combine first_name and last_name if they exist, or add name
                if (Schema::hasColumn('students', 'first_name') && Schema::hasColumn('students', 'last_name')) {
                    // We'll handle name generation in a seeder or keep using first_name/last_name
                    $table->string('name')->nullable()->after('admission_no');
                } else {
                    $table->string('name')->nullable()->after('admission_no');
                }
            }
            if (!Schema::hasColumn('students', 'father_name')) {
                $table->string('father_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('students', 'phone')) {
                $table->string('phone')->nullable()->after('father_name');
            }
            if (!Schema::hasColumn('students', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('students', 'current_class_id')) {
                $table->foreignId('current_class_id')->nullable()->after('email')->constrained('classes')->onDelete('set null');
            }
            if (!Schema::hasColumn('students', 'current_session_id')) {
                $table->foreignId('current_session_id')->nullable()->after('current_class_id')->constrained('sessions')->onDelete('set null');
            }
            if (!Schema::hasColumn('students', 'status')) {
                $table->enum('status', ['active', 'left', 'passed_out'])->default('active')->after('current_session_id');
            } else {
                // Update existing status column if it exists with different enum values
                $table->enum('status', ['active', 'left', 'passed_out'])->default('active')->change();
            }
        });

        // Add indexes and unique constraints
        Schema::table('students', function (Blueprint $table) {
            $table->index(['school_id', 'current_session_id', 'current_class_id'], 'students_school_session_class_idx');
            $table->unique(['school_id', 'admission_no'], 'students_school_admission_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_school_admission_no_unique');
            $table->dropIndex('students_school_session_class_idx');
            
            $columns = ['school_id', 'admission_no', 'name', 'father_name', 'phone', 'email', 
                       'current_class_id', 'current_session_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('students', $column)) {
                    if (in_array($column, ['current_class_id', 'current_session_id', 'school_id'])) {
                        $table->dropForeign([$column]);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
