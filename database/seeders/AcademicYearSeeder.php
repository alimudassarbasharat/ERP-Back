<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currentYear = Carbon::now()->year;
        
        $academicYears = [
            [
                'name' => ($currentYear - 1) . '-' . $currentYear,
                'start_date' => Carbon::create($currentYear - 1, 7, 1), // July 1st
                'end_date' => Carbon::create($currentYear, 6, 30), // June 30th
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => $currentYear . '-' . ($currentYear + 1),
                'start_date' => Carbon::create($currentYear, 7, 1), // July 1st
                'end_date' => Carbon::create($currentYear + 1, 6, 30), // June 30th
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => ($currentYear + 1) . '-' . ($currentYear + 2),
                'start_date' => Carbon::create($currentYear + 1, 7, 1), // July 1st
                'end_date' => Carbon::create($currentYear + 2, 6, 30), // June 30th
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($academicYears as $year) {
            $exists = DB::table('academic_years')->where('name', $year['name'])->exists();
            if (!$exists) {
                DB::table('academic_years')->insert($year);
            }
        }
    }
}