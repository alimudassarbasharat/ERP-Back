<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FeeHead;
use App\Models\School;

class FeeHeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first school or create a default one for seeding
        $school = School::first();
        
        if (!$school) {
            $this->command->warn('No school found. Please create a school first.');
            return;
        }

        $feeHeads = [
            ['name' => 'Tuition Fee', 'frequency' => 'monthly'],
            ['name' => 'Transport Fee', 'frequency' => 'monthly'],
            ['name' => 'Exam Fee', 'frequency' => 'yearly'],
            ['name' => 'Library Fee', 'frequency' => 'yearly'],
            ['name' => 'Lab Fee', 'frequency' => 'yearly'],
            ['name' => 'Sports Fee', 'frequency' => 'yearly'],
            ['name' => 'Admission Fee', 'frequency' => 'one_time'],
            ['name' => 'Registration Fee', 'frequency' => 'one_time'],
        ];

        foreach ($feeHeads as $feeHead) {
            FeeHead::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $feeHead['name'],
                ],
                [
                    'frequency' => $feeHead['frequency'],
                ]
            );
        }

        $this->command->info('Fee heads seeded successfully for school: ' . $school->name);
    }
}
