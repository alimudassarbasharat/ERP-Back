<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QuickSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create basic users
        DB::table('users')->insertOrIgnore([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create admin user
        DB::table('admins')->insertOrIgnore([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role_id' => 2,
            'status' => 'active',
            'merchant_id' => 'MERCH123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create basic classes
        $classes = [
            ['id' => 1, 'name' => 'Class 1', 'description' => 'First Grade'],
            ['id' => 2, 'name' => 'Class 2', 'description' => 'Second Grade'],
            ['id' => 3, 'name' => 'Class 3', 'description' => 'Third Grade'],
        ];

        foreach ($classes as $class) {
            DB::table('classes')->insertOrIgnore(array_merge($class, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Create subjects
        $subjects = [
            ['id' => 1, 'name' => 'Mathematics', 'code' => 'MATH'],
            ['id' => 2, 'name' => 'English', 'code' => 'ENG'],
            ['id' => 3, 'name' => 'Science', 'code' => 'SCI'],
            ['id' => 4, 'name' => 'History', 'code' => 'HIST'],
        ];

        foreach ($subjects as $subject) {
            DB::table('subjects')->insertOrIgnore(array_merge($subject, [
                'description' => $subject['name'] . ' subject',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Create sections
        $sections = [
            ['id' => 1, 'name' => 'Section A', 'description' => 'Section A', 'status' => 'active'],
            ['id' => 2, 'name' => 'Section B', 'description' => 'Section B', 'status' => 'active'],
        ];

        foreach ($sections as $section) {
            DB::table('sections')->insertOrIgnore(array_merge($section, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Create sample students
        $students = [
            [
                'id' => 1,
                'first_name' => 'Ahmed',
                'last_name' => 'Ali',
                'email' => 'ahmed@test.com',
                'date_of_birth' => '2010-01-15',
                'gender' => 'male',
                'admission_number' => 'ADM001',
                'admission_date' => '2024-01-01',
                'status' => 'active',
                'class_id' => 1,
                'section_id' => 1,
            ],
            [
                'id' => 2,
                'first_name' => 'Fatima',
                'last_name' => 'Khan',
                'email' => 'fatima@test.com',
                'date_of_birth' => '2010-03-20',
                'gender' => 'female',
                'admission_number' => 'ADM002',
                'admission_date' => '2024-01-01',
                'status' => 'active',
                'class_id' => 1,
                'section_id' => 1,
            ]
        ];

        foreach ($students as $student) {
            DB::table('students')->insertOrIgnore(array_merge($student, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Create sample teacher
        DB::table('teachers')->insertOrIgnore([
            'id' => 1,
            'first_name' => 'Teacher',
            'last_name' => 'Ahmed',
            'employee_code' => 'EMP001',
            'email' => 'teacher@test.com',
            'password' => Hash::make('password'),
            'username' => 'teacher001',
            'status' => 'active',
            'designation' => 'Senior Teacher',
            'department_id' => 1,
            'qualification' => 'Masters',
            'years_of_experience' => 5,
            'joining_date' => '2020-01-01',
            'salary' => 50000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create exam
        DB::table('exams')->insertOrIgnore([
            'id' => 1,
            'name' => 'Mid Term Exam',
            'description' => 'Mid term examination',
            'start_date' => '2024-10-01',
            'end_date' => '2024-10-15',
            'status' => 'upcoming',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✅ Database seeded successfully with sample data!\n";
        echo "📧 Admin: admin@test.com | Password: password\n";
        echo "👤 User: user@test.com | Password: password\n";
        echo "👨‍🏫 Teacher: teacher@test.com | Password: password\n";
    }
}
