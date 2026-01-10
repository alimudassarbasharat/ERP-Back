<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class UpdateUsersMerchantIdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Updates merchant_id to 'MERCH_CHAT_TEST' for 5000 students
     */
    public function run(): void
    {
        $merchantId = 'MERCH_CHAT_TEST';
        $limit = 5000;
        
        $this->command->info("Starting to update merchant_id to '{$merchantId}' for {$limit} students...");
        
        // Get total count of students
        $totalStudents = Student::count();
        $this->command->info("Total students in database: {$totalStudents}");
        
        // Use bulk update for better performance
        // First, get IDs of students to update (limit to 5000)
        $studentIds = Student::select('id')
            ->limit($limit)
            ->pluck('id')
            ->toArray();
        
        if (empty($studentIds)) {
            $this->command->warn("No students found to update.");
            return;
        }
        
        $this->command->info("Found " . count($studentIds) . " students to update. Starting bulk update...");
        
        // Update in chunks for better performance and memory management
        $chunkSize = 500;
        $updated = 0;
        $chunks = array_chunk($studentIds, $chunkSize);
        
        foreach ($chunks as $chunk) {
            $count = DB::table('students')
                ->whereIn('id', $chunk)
                ->update([
                    'merchant_id' => $merchantId,
                    'updated_at' => now()
                ]);
            
            $updated += $count;
            
            if ($updated % 500 === 0 || $updated === count($studentIds)) {
                $this->command->info("Updated {$updated} students...");
            }
        }
        
        $this->command->info("✅ Successfully updated {$updated} students with merchant_id: '{$merchantId}'");
        
        // Verify the update
        $verifyCount = Student::where('merchant_id', $merchantId)->count();
        $this->command->info("Verification: {$verifyCount} students now have merchant_id = '{$merchantId}'");
    }
}
