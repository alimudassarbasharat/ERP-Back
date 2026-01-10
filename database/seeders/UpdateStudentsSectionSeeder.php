<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateStudentsSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Updates section_id for students with specific merchant_id
     */
    public function run(): void
    {
        $merchantId = 'MERCH_CHAT_TEST';
        
        $this->command->info("Starting to update section_id for students with merchant_id: '{$merchantId}'...");
        
        // Check if section_id column exists in students table
        if (!Schema::hasColumn('students', 'section_id')) {
            $this->command->warn("section_id column does not exist in students table. Skipping update.");
            return;
        }
        
        // Get all sections (with same merchant_id if available)
        $sections = Section::query();
        
        // If sections table has merchant_id column, filter by it
        if (Schema::hasColumn('sections', 'merchant_id')) {
            $sections->where('merchant_id', $merchantId);
        }
        
        $sections = $sections->where('status', 'active')
            ->orWhereNull('status')
            ->get();
        
        if ($sections->isEmpty()) {
            $this->command->warn("No active sections found. Creating default sections...");
            
            // Create default sections if none exist
            $defaultSections = [
                ['name' => 'Section A', 'description' => 'Section A'],
                ['name' => 'Section B', 'description' => 'Section B'],
                ['name' => 'Section C', 'description' => 'Section C'],
            ];
            
            foreach ($defaultSections as $sectionData) {
                $sectionData['status'] = 'active';
                $sectionData['created_at'] = now();
                $sectionData['updated_at'] = now();
                
                if (Schema::hasColumn('sections', 'merchant_id')) {
                    $sectionData['merchant_id'] = $merchantId;
                }
                
                Section::create($sectionData);
            }
            
            // Re-fetch sections
            $sections = Section::query();
            if (Schema::hasColumn('sections', 'merchant_id')) {
                $sections->where('merchant_id', $merchantId);
            }
            $sections = $sections->get();
        }
        
        $this->command->info("Found " . $sections->count() . " sections available.");
        
        // Get all students with the specified merchant_id
        $students = Student::where('merchant_id', $merchantId)->get();
        
        if ($students->isEmpty()) {
            $this->command->warn("No students found with merchant_id: '{$merchantId}'");
            return;
        }
        
        $this->command->info("Found " . $students->count() . " students to update.");
        
        // Get section IDs as array
        $sectionIds = $sections->pluck('id')->toArray();
        
        if (empty($sectionIds)) {
            $this->command->error("No section IDs available. Cannot update students.");
            return;
        }
        
        // Update students in chunks
        $chunkSize = 500;
        $updated = 0;
        $chunks = $students->chunk($chunkSize);
        
        foreach ($chunks as $chunk) {
            foreach ($chunk as $student) {
                $sectionId = null;
                
                // If student has class_id, try to find sections for that class
                if ($student->class_id && Schema::hasColumn('sections', 'class_id')) {
                    $classSections = Section::where('class_id', $student->class_id);
                    
                    if (Schema::hasColumn('sections', 'merchant_id')) {
                        $classSections->where('merchant_id', $merchantId);
                    }
                    
                    $classSections = $classSections->get();
                    
                    if ($classSections->isNotEmpty()) {
                        // Assign random section from student's class
                        $sectionId = $classSections->random()->id;
                    }
                }
                
                // If no class-specific section found, assign random section
                if (!$sectionId) {
                    $sectionId = $sectionIds[array_rand($sectionIds)];
                }
                
                $student->section_id = $sectionId;
                $student->updated_at = now();
                $student->save();
                
                $updated++;
            }
            
            if ($updated % 500 === 0) {
                $this->command->info("Updated {$updated} students...");
            }
        }
        
        $this->command->info("✅ Successfully updated {$updated} students with section_id");
        
        // Verify the update
        $studentsWithSection = Student::where('merchant_id', $merchantId)
            ->whereNotNull('section_id')
            ->count();
        
        $this->command->info("Verification: {$studentsWithSection} students now have section_id assigned");
    }
}
