<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeeDefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing classes
        $classes = DB::table('classses')->get();
        
        if ($classes->count() > 0) {
            foreach ($classes as $class) {
                DB::table('fee_defaults')->insert([
                    'class_id' => $class->id,
                    'monthly_fee' => rand(5000, 15000), // Random fee between 5000-15000
                    'effective_from' => now()->format('Y-m-d'),
                    'status' => 'Active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            // If no classes exist, create some sample fee defaults
            DB::table('fee_defaults')->insert([
                [
                    'class_id' => 1,
                    'monthly_fee' => 8000,
                    'effective_from' => now()->format('Y-m-d'),
                    'status' => 'Active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'class_id' => 2,
                    'monthly_fee' => 10000,
                    'effective_from' => now()->format('Y-m-d'),
                    'status' => 'Active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'class_id' => 3,
                    'monthly_fee' => 12000,
                    'effective_from' => now()->format('Y-m-d'),
                    'status' => 'Inactive',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
} 