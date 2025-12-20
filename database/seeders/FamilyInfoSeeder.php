<?php

namespace Database\Seeders;

use App\Models\FamilyInfo;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class FamilyInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $students = Student::all();

        foreach ($students as $student) {
            FamilyInfo::create([
                'father_name' => $faker->name('male'),
                'father_cnic' => $faker->unique()->numerify('#####-#######-#'),
                'father_occupation' => $faker->jobTitle(),
                'mother_name' => $faker->name('female'),
                'home_address' => $faker->address(),
                'student_id' => $student->id,
                'merchant_id' => $student->merchant_id,
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
            ]);
        }
    }
} 