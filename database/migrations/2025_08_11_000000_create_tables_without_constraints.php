<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTablesWithoutConstraints extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create roles table first (needed by admins)
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        // Create permissions table
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        // Create model_has_permissions table
        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }

        // Create model_has_roles table
        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }

        // Create role_has_permissions table
        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        // Create departments table
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedBigInteger('head_id')->nullable();
                $table->text('description')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create admins table without foreign key constraints
        if (!Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('phone_number')->nullable();
                $table->unsignedBigInteger('role_id')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->string('merchant_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create students table
        if (!Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->id();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->unique()->nullable();
                $table->date(column: 'date_of_birth')->nullable();
                $table->enum('gender', ['male', 'female', 'other']);
                $table->string('phone_number')->nullable();
                $table->text('address')->nullable();
                $table->string('admission_number')->unique();
                $table->date(column: 'admission_date')->nullable();
                $table->enum('status', ['active', 'inactive', 'graduated', 'dropped_out'])->default('active');
                $table->text('medical_conditions')->nullable();
                $table->text('emergency_contact')->nullable();
                $table->string('photo_path')->nullable();
                $table->unsignedBigInteger('class_id')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create classes table
        if (!Schema::hasTable('classes')) {
            Schema::create('classes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create sections table
        if (!Schema::hasTable('sections')) {
            Schema::create('sections', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('class_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        // Create subjects table
        if (!Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Create teachers table
        if (!Schema::hasTable('teachers')) {
            Schema::create('teachers', function (Blueprint $table) {
                $table->id();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('employee_id')->unique();
                $table->date('date_of_birth');
                $table->enum('gender', ['male', 'female', 'other']);
                $table->string('phone_number');
                $table->text('address');
                $table->date('hire_date');
                $table->enum('employment_status', ['active', 'inactive', 'terminated', 'resigned'])->default('active');
                $table->decimal('salary', 10, 2);
                $table->text('qualifications')->nullable();
                $table->string('photo_path')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create exams table
        if (!Schema::hasTable('exams')) {
            Schema::create('exams', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // Create academic_years table
        if (!Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }

        // Add soft deletes to users if not exists
        if (!Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add department_id to users if not exists
        if (!Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('students');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
}
