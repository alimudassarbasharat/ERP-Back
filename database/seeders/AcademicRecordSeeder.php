<?php

namespace Database\Seeders;

use App\Models\AcademicRecord;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class AcademicRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $students = Student::all();

        foreach ($students as $student) {
            AcademicRecord::create([
                'last_admission_no' => $faker->optional()->numerify('ADM-#####'),
                'has_sibling' => $faker->boolean(),
                'session' => $faker->year() . '-' . ($faker->year() + 1),
                'student_id' => $student->id,
                'merchant_id' => $student->merchant_id,
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
            ]);
        }
    }
} 