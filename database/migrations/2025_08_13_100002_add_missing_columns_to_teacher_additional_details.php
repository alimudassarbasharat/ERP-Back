<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teacher_additional_details')) {
            return;
        }

        Schema::table('teacher_additional_details', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_additional_details', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->after('id');
                // Optional FK if teachers table exists
                if (Schema::hasTable('teachers')) {
                    try {
                        $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
                    } catch (\Throwable $e) {
                        // Skip FK if platform does not allow adding here
                    }
                }
            }
            if (!Schema::hasColumn('teacher_additional_details', 'bank_account_details')) {
                $table->text('bank_account_details')->nullable()->after('teacher_id');
            }
            if (!Schema::hasColumn('teacher_additional_details', 'remarks')) {
                $table->text('remarks')->nullable()->after('bank_account_details');
            }
            if (!Schema::hasColumn('teacher_additional_details', 'merchant_id')) {
                $table->string('merchant_id')->nullable();
            }
            if (!Schema::hasColumn('teacher_additional_details', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('teacher_additional_details')) {
            return;
        }
        Schema::table('teacher_additional_details', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_additional_details', 'remarks')) {
                $table->dropColumn('remarks');
            }
            if (Schema::hasColumn('teacher_additional_details', 'bank_account_details')) {
                $table->dropColumn('bank_account_details');
            }
            // Avoid dropping FK and teacher_id automatically to prevent data loss; only drop column if it exists and no FK
            if (Schema::hasColumn('teacher_additional_details', 'teacher_id')) {
                try {
                    $table->dropForeign(['teacher_id']);
                } catch (\Throwable $e) {
                    // ignore if FK not present
                }
                $table->dropColumn('teacher_id');
            }
            if (Schema::hasColumn('teacher_additional_details', 'merchant_id')) {
                $table->dropColumn('merchant_id');
            }
            if (Schema::hasColumn('teacher_additional_details', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};


