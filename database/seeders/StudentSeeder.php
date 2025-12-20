<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $genders = ['Male', 'Female', 'Other'];
        $religions = ['Islam', 'Christianity', 'Hinduism', 'Other'];
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        for ($i = 0; $i < 10000; $i++) {
            Student::create([
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'roll_number' => $faker->unique()->numerify('ROLL-#####'),
                'gender' => $faker->randomElement($genders),
                'cnic_number' => $faker->unique()->numerify('#####-#######-#'),
                'DOB' => $faker->date('Y-m-d', '-5 years'),
                'DOA' => $faker->date('Y-m-d', 'now'),
                'religion' => $faker->randomElement($religions),
                'cast' => $faker->word(),
                'blood_group' => $faker->randomElement($bloodGroups),
                'photo_path' => 'default.jpg',
                'merchant_id' => 'MERCHANT-' . $faker->numerify('#####'),
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
            ]);
        }
    }
} 