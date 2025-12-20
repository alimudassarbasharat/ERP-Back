<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Department;
use App\Models\Program;
use App\Models\Institution;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        // Create test institution
        $institution = Institution::create([
            'name' => 'Test University',
            'logo_path' => 'institutions/logo.png',
            'signature_path' => 'institutions/signature.png'
        ]);

        // Create test department
        $department = Department::create([
            'name' => 'Computer Science',
            'institution_id' => $institution->id
        ]);

        // Create test program
        $program = Program::create([
            'name' => 'Bachelor of Computer Science',
            'department_id' => $department->id
        ]);

        // Create test student
        Student::create([
            'name' => 'Test Student',
            'student_id' => 'CS2024001',
            'department_id' => $department->id,
            'program_id' => $program->id,
            'photo_path' => 'students/test-student.jpg',
            'valid_until' => now()->addYears(4),
            'institution_id' => $institution->id
        ]);
    }
} 