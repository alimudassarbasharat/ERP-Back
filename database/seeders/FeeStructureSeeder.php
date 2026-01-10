<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FeeStructure;
use App\Models\School;
use App\Models\Session;
use App\Models\Classes;
use App\Models\FeeHead;

class FeeStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $school = School::first();
        
        if (!$school) {
            $this->command->warn('No school found. Please create a school first.');
            return;
        }

        $session = Session::where('school_id', $school->id)->where('is_active', true)->first();
        
        if (!$session) {
            $this->command->warn('No active session found. Please create an active session first.');
            return;
        }

        $classes = Classes::where('school_id', $school->id)->get();
        $feeHeads = FeeHead::where('school_id', $school->id)->get();

        if ($classes->isEmpty() || $feeHeads->isEmpty()) {
            $this->command->warn('No classes or fee heads found. Please seed them first.');
            return;
        }

        // Sample fee structure: Tuition fee varies by class
        $tuitionFeeHead = $feeHeads->where('name', 'Tuition Fee')->first();
        $transportFeeHead = $feeHeads->where('name', 'Transport Fee')->first();
        $examFeeHead = $feeHeads->where('name', 'Exam Fee')->first();

        foreach ($classes as $class) {
            // Tuition fee - varies by class sequence (higher class = higher fee)
            if ($tuitionFeeHead) {
                $tuitionAmount = 5000 + ($class->sequence ?? 1) * 500;
                FeeStructure::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'session_id' => $session->id,
                        'class_id' => $class->id,
                        'fee_head_id' => $tuitionFeeHead->id,
                    ],
                    [
                        'amount' => $tuitionAmount,
                    ]
                );
            }

            // Transport fee - same for all classes
            if ($transportFeeHead) {
                FeeStructure::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'session_id' => $session->id,
                        'class_id' => $class->id,
                        'fee_head_id' => $transportFeeHead->id,
                    ],
                    [
                        'amount' => 2000,
                    ]
                );
            }

            // Exam fee - same for all classes
            if ($examFeeHead) {
                FeeStructure::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'session_id' => $session->id,
                        'class_id' => $class->id,
                        'fee_head_id' => $examFeeHead->id,
                    ],
                    [
                        'amount' => 1000,
                    ]
                );
            }
        }

        $this->command->info('Fee structures seeded successfully for ' . $classes->count() . ' classes.');
    }
}
