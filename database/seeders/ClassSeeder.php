<?php

namespace Database\Seeders;

use App\Models\Classs;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            Classs::create([
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
            ]);
        }
    }
} 