<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Academic Years table
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Update subjects table if needed
        if (!Schema::hasColumn('subjects', 'credit_hours')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->integer('credit_hours')->default(3);
                $table->boolean('is_active')->default(true);
            });
        }

        // Update teachers table if needed
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
            }
            if (!Schema::hasColumn('teachers', 'employee_id')) {
                $table->string('employee_id')->unique()->nullable();
            }
            if (!Schema::hasColumn('teachers', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('teachers', 'address')) {
                $table->text('address')->nullable();
            }
            if (!Schema::hasColumn('teachers', 'experience')) {
                $table->integer('experience')->nullable();
            }
            if (!Schema::hasColumn('teachers', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
        
        // Add foreign key separately
        if (!Schema::hasColumn('teachers', 'user_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Teacher-Subject pivot table
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('subject_id');
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->unique(['teacher_id', 'subject_id']);
        });

        // Teacher-Class pivot table
        Schema::create('teacher_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('class_id');
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->unique(['teacher_id', 'class_id']);
        });

        // Class-Subject pivot table
        Schema::create('class_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->unique(['class_id', 'subject_id']);
        });

        // Comprehensive Attendance Records table
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('academic_year_id');
            $table->date('attendance_date');
            $table->enum('attendance_mode', ['daily', 'period_wise'])->default('daily');
            $table->integer('period_number')->nullable();
            $table->enum('status', [
                'present', 
                'absent', 
                'late', 
                'leave', 
                'medical', 
                'online_present', 
                'proxy_suspected'
            ])->default('present');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->unsignedBigInteger('marked_by');
            $table->timestamp('marked_at');
            $table->text('remarks')->nullable();
            
            // Regularization fields
            $table->boolean('is_regularized')->default(false);
            $table->text('regularization_reason')->nullable();
            $table->unsignedBigInteger('regularization_approved_by')->nullable();
            $table->timestamp('regularization_approved_at')->nullable();
            
            // Notification fields
            $table->boolean('parent_notified')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('set null');
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
            $table->foreign('marked_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('regularization_approved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for performance
            $table->index(['student_id', 'attendance_date']);
            $table->index(['class_id', 'section_id', 'attendance_date']);
            $table->index(['subject_id', 'attendance_date']);
            $table->index(['teacher_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
            
            // Unique constraint for daily attendance
            $table->unique([
                'student_id', 
                'attendance_date', 
                'attendance_mode', 
                'subject_id', 
                'period_number'
            ], 'unique_attendance_record');
        });

        // Attendance Settings table
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value');
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique('key');
        });

        // Insert default settings
        DB::table('attendance_settings')->insert([
            [
                'key' => 'minimum_attendance_percentage',
                'value' => '75',
                'type' => 'integer',
                'description' => 'Minimum attendance percentage required',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'auto_mark_absent_time',
                'value' => '10:00',
                'type' => 'string',
                'description' => 'Time after which students are automatically marked absent',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'late_arrival_threshold',
                'value' => '15',
                'type' => 'integer',
                'description' => 'Minutes after which arrival is considered late',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'enable_parent_notifications',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable SMS/Email notifications to parents',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'enable_regularization',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Allow students to request attendance regularization',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('class_subjects');
        Schema::dropIfExists('teacher_classes');
        Schema::dropIfExists('teacher_subjects');
        Schema::dropIfExists('academic_years');
        
        // Remove added columns from existing tables
        if (Schema::hasColumn('subjects', 'credit_hours')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn(['credit_hours', 'is_active']);
            });
        }
        
        if (Schema::hasColumn('teachers', 'user_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn([
                    'user_id', 
                    'employee_id', 
                    'phone', 
                    'address', 
                    'qualification', 
                    'experience', 
                    'department', 
                    'is_active'
                ]);
            });
        }
    }
};
