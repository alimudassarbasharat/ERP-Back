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
        Schema::table('challans', function (Blueprint $table) {
            // pdf_path already exists, just add status and generated_at
            if (!Schema::hasColumn('challans', 'pdf_status')) {
                $table->enum('pdf_status', ['pending', 'generating', 'completed', 'failed'])->default('pending')->after('pdf_path');
            }
            if (!Schema::hasColumn('challans', 'pdf_generated_at')) {
                $table->dateTime('pdf_generated_at')->nullable()->after('pdf_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('challans', function (Blueprint $table) {
            $table->dropColumn(['pdf_status', 'pdf_generated_at']);
        });
    }
};
