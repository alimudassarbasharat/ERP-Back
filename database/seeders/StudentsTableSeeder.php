<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class StudentsTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('en_PK'); // Pakistan locale

        // Sample Pakistani castes
        $pakistaniCasts = [
            'Jutt', 'Rajput', 'Arain', 'Gujjar', 'Mughal', 
            'Pathan', 'Syed', 'Sheikh', 'Memon', 'Baloch'
        ];

        for ($i = 1; $i <= 10000; $i++) {
            $gender = $faker->randomElement(['Male', 'Female']);
            $firstName = $gender === 'Male' 
                ? $faker->firstNameMale() 
                : $faker->firstNameFemale();

            Student::create([
                'first_name'    => $firstName,
                'last_name'     => $faker->lastName,
                'roll_number'   => $i,
                'gender'        => $gender,
                'cnic_number'  => $faker->unique()->regexify('[0-9]{5}-[0-9]{7}-[0-9]{1}'),
                'DOB'          => $faker->dateTimeBetween('-18 years', '-5 years')->format('Y-m-d'),
                'DOA'          => $faker->dateTimeBetween('-2 years')->format('Y-m-d'),
                'religion'     => $faker->randomElement(['Islam', 'Christianity', 'Hinduism']),
                'cast'         => $faker->randomElement($pakistaniCasts),
                'blood_group'  => $faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
                'photo_path'   => 'students/'.($gender === 'Male' ? 'male' : 'female').'_'.rand(1,5).'.jpg',
            //    'merchant_id'   => \Str::random(10), // Generates random 10-character string
                 'merchant_id' => 'sfdgfggersdfsfdgd433',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}