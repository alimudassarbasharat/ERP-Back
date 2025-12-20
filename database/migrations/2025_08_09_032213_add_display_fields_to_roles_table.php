<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->text('description')->nullable()->after('display_name');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('module')->nullable()->after('guard_name');
            $table->string('action')->nullable()->after('module');
            $table->text('description')->nullable()->after('action');
        });
    }

    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'description']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['module', 'action', 'description']);
        });
    }
};