<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $departments = [
            [
                'name' => 'Administration',
                'code' => 'ADMIN',
                'description' => 'Administrative department',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Academic',
                'code' => 'ACAD',
                'description' => 'Academic affairs department',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Science',
                'code' => 'SCI',
                'description' => 'Science department',
                'parent_id' => 2, // Under Academic
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Mathematics',
                'code' => 'MATH',
                'description' => 'Mathematics department',
                'parent_id' => 2, // Under Academic
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'English',
                'code' => 'ENG',
                'description' => 'English language department',
                'parent_id' => 2, // Under Academic
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'description' => 'Finance and accounts department',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Human resources department',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'IT Support',
                'code' => 'IT',
                'description' => 'Information technology department',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($departments as $department) {
            $exists = DB::table('departments')->where('code', $department['code'])->exists();
            if (!$exists) {
                DB::table('departments')->insert($department);
            }
        }
    }
}