<?php

namespace Database\Seeders;

use App\Models\ContactInformation;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ContactInformationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $students = Student::all();
        $provinces = ['Punjab', 'Sindh', 'KPK', 'Balochistan', 'AJK', 'GB', 'Islamabad'];

        foreach ($students as $student) {
            ContactInformation::create([
                'reporting_number' => $faker->phoneNumber(),
                'whatsapp_number' => $faker->phoneNumber(),
                'email' => $faker->unique()->safeEmail(),
                'address' => $faker->address(),
                'province' => $faker->randomElement($provinces),
                'district' => $faker->city(),
                'city' => $faker->city(),
                'village' => $faker->optional()->city(),
                'postal_code' => $faker->numerify('#####'),
                'student_id' => $student->id,
                'merchant_id' => $student->merchant_id,
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
            ]);
        }
    }
} 