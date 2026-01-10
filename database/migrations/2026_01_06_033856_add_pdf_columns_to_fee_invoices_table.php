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
        Schema::table('fee_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_invoices', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('generated_at');
            }
            if (!Schema::hasColumn('fee_invoices', 'pdf_status')) {
                $table->enum('pdf_status', ['pending', 'generating', 'completed', 'failed'])->default('pending')->after('pdf_path');
            }
            if (!Schema::hasColumn('fee_invoices', 'pdf_generated_at')) {
                $table->dateTime('pdf_generated_at')->nullable()->after('pdf_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropColumn(['pdf_path', 'pdf_status', 'pdf_generated_at']);
        });
    }
};
