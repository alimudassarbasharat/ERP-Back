<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                if (!Schema::hasColumn('classes', 'name')) {
                    $table->string('name')->nullable()->after('id');
                }
                if (!Schema::hasColumn('classes', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                if (Schema::hasColumn('classes', 'description')) {
                    $table->dropColumn('description');
                }
                if (Schema::hasColumn('classes', 'name')) {
                    $table->dropColumn('name');
                }
            });
        }
    }
};


