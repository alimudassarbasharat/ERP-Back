<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $this->seedClasses($faker);
        $this->seedSections($faker);
        $this->seedSubjects($faker);
        $this->seedTeachers($faker);
        $this->seedStudents($faker);
        $this->seedFeeSummaries($faker);
    }

    private function has(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    private function seedClasses($faker): void
    {
        if (!Schema::hasTable('classes')) return;
        if (DB::table('classes')->count() > 0) return;

        $classNames = ['Nursery', 'Prep', 'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10'];
        foreach ($classNames as $name) {
            $row = [
                'merchant_id' => 'DEFAULT_TENANT',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($this->has('classes', 'name')) {
                $row['name'] = $name;
            }
            if ($this->has('classes', 'description')) {
                $row['description'] = $faker->sentence(6);
            }
            DB::table('classes')->insert($row);
        }
    }

    private function seedSections($faker): void
    {
        if (!Schema::hasTable('sections')) return;
        if (DB::table('sections')->count() > 0) return;

        $sectionNames = ['A', 'B', 'C'];
        $classes = [];
        if (Schema::hasTable('classes')) {
            if ($this->has('classes', 'name')) {
                $classes = DB::table('classes')->select('id', 'name')->get();
            } else {
                $classes = DB::table('classes')->select('id')->get();
            }
        }

        foreach ($classes as $class) {
            foreach ($sectionNames as $letter) {
                $row = [
                    'merchant_id' => 'DEFAULT_TENANT',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                // Build a globally-unique section name if 'name' column is unique
                if ($this->has('sections', 'name')) {
                    $base = $letter;
                    if (isset($class->name)) {
                        $base = $class->name . ' - ' . $letter;
                    } elseif (isset($class->id)) {
                        $base = $letter . '-' . $class->id;
                    }
                    $row['name'] = $base;
                    // Skip if a row with same name already exists (satisfy unique index)
                    if (DB::table('sections')->where('name', $row['name'])->exists()) {
                        continue;
                    }
                }
                if ($this->has('sections', 'description')) $row['description'] = $faker->sentence(8);
                if ($this->has('sections', 'class_id')) $row['class_id'] = $class->id;
                if ($this->has('sections', 'status')) $row['status'] = 'active';
                DB::table('sections')->insert($row);
            }
        }
    }

    private function seedSubjects($faker): void
    {
        if (!Schema::hasTable('subjects')) return;
        if (DB::table('subjects')->count() > 0) return;

        $subjects = ['English', 'Mathematics', 'Science', 'Computer', 'Urdu', 'Islamiyat', 'Physics', 'Chemistry', 'Biology'];
        foreach ($subjects as $s) {
            $row = [
                'merchant_id' => 'DEFAULT_TENANT',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($this->has('subjects', 'name')) $row['name'] = $s;
            if ($this->has('subjects', 'code')) $row['code'] = Str::upper(Str::slug($s, '_'));
            if ($this->has('subjects', 'description')) $row['description'] = $faker->sentence(10);
            DB::table('subjects')->insert($row);
        }
    }

    private function seedTeachers($faker): void
    {
        if (!Schema::hasTable('teachers')) return;
        if (DB::table('teachers')->count() > 0) return;

        $departmentIds = Schema::hasTable('departments') ? DB::table('departments')->pluck('id')->all() : [];
        for ($i = 1; $i <= 15; $i++) {
            $first = $faker->firstName();
            $last = $faker->lastName();
            $email = Str::slug($first.'.'.$last).$i.'@school.test';
            $row = [
                'merchant_id' => 'DEFAULT_TENANT',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Common fields
            if ($this->has('teachers', 'first_name')) $row['first_name'] = $first;
            if ($this->has('teachers', 'last_name')) $row['last_name'] = $last;
            if ($this->has('teachers', 'email')) $row['email'] = $email;
            if ($this->has('teachers', 'password')) $row['password'] = Hash::make('password');

            // Older/newer schema variants
            if ($this->has('teachers', 'username')) $row['username'] = Str::lower($first.$i);
            if ($this->has('teachers', 'status')) $row['status'] = 'active';
            if ($this->has('teachers', 'designation')) $row['designation'] = $faker->randomElement(['teacher','assistant_teacher','head_teacher']);
            if ($this->has('teachers', 'department_id') && !empty($departmentIds)) $row['department_id'] = $faker->randomElement($departmentIds);
            if ($this->has('teachers', 'qualification')) $row['qualification'] = $faker->randomElement(['B.Ed', 'M.Ed', 'MSc', 'BS']);
            if ($this->has('teachers', 'years_of_experience')) $row['years_of_experience'] = $faker->numberBetween(0, 15);
            if ($this->has('teachers', 'joining_date')) $row['joining_date'] = $faker->date('Y-m-d', 'now');
            if ($this->has('teachers', 'salary')) $row['salary'] = $faker->numberBetween(30000, 120000);

            // Alternate schema columns
            if ($this->has('teachers', 'employee_code')) $row['employee_code'] = 'EMP-'.Str::upper(Str::random(6));
            if ($this->has('teachers', 'employee_id')) $row['employee_id'] = 'E'.str_pad((string)$i, 5, '0', STR_PAD_LEFT);
            if ($this->has('teachers', 'date_of_birth')) $row['date_of_birth'] = $faker->date('Y-m-d', '-22 years');
            if ($this->has('teachers', 'gender')) $row['gender'] = $faker->randomElement(['male','female','other']);
            if ($this->has('teachers', 'phone_number')) $row['phone_number'] = '03'.mt_rand(100000000, 999999999);
            if ($this->has('teachers', 'address')) $row['address'] = $faker->address();
            if ($this->has('teachers', 'hire_date')) $row['hire_date'] = $faker->date('Y-m-d', 'now');
            if ($this->has('teachers', 'employment_status')) $row['employment_status'] = 'active';

            DB::table('teachers')->insert($row);
        }
    }

    private function seedStudents($faker): void
    {
        if (!Schema::hasTable('students')) return;
        if (DB::table('students')->count() > 0) return;

        $classIds = Schema::hasTable('classes') ? DB::table('classes')->pluck('id')->all() : [];
        $sectionIds = Schema::hasTable('sections') ? DB::table('sections')->pluck('id')->all() : [];

        for ($i = 1; $i <= 100; $i++) {
            $first = $faker->firstName();
            $last = $faker->lastName();
            $row = [
                'merchant_id' => 'DEFAULT_TENANT',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Newer flexible schema
            if ($this->has('students', 'first_name')) $row['first_name'] = $first;
            if ($this->has('students', 'last_name')) $row['last_name'] = $last;
            if ($this->has('students', 'email')) $row['email'] = Str::slug($first.'.'.$last).$i.'@student.test';
            if ($this->has('students', 'date_of_birth')) $row['date_of_birth'] = $faker->date('Y-m-d', '-12 years');
            if ($this->has('students', 'gender')) {
                // Choose case based on schema variant
                $genderValues = ['male','female','other'];
                if ($this->has('students', 'date_of_birth') || $this->has('students', 'admission_date')) {
                    $genderValues = ['Male','Female','Other'];
                }
                $row['gender'] = $faker->randomElement($genderValues);
            }
            if ($this->has('students', 'phone_number')) $row['phone_number'] = '03'.mt_rand(100000000, 999999999);
            if ($this->has('students', 'address')) $row['address'] = $faker->address();
            if ($this->has('students', 'admission_number')) $row['admission_number'] = 'ADM'.str_pad((string)$i, 6, '0', STR_PAD_LEFT);
            if ($this->has('students', 'admission_date')) $row['admission_date'] = $faker->date('Y-m-d', 'now');
            if ($this->has('students', 'status')) $row['status'] = 'active';
            if ($this->has('students', 'class_id') && !empty($classIds)) $row['class_id'] = $faker->randomElement($classIds);
            if ($this->has('students', 'section_id') && !empty($sectionIds)) $row['section_id'] = $faker->randomElement($sectionIds);

            // Older schema fields
            if ($this->has('students', 'roll_number')) $row['roll_number'] = 'ROLL-'.str_pad((string)$i, 5, '0', STR_PAD_LEFT);
            if ($this->has('students', 'cnic_number')) $row['cnic_number'] = $faker->numerify('#####-#######-#');
            if ($this->has('students', 'admission_date')) $row['admission_date'] = $faker->date('Y-m-d', 'now');
            if ($this->has('students', 'religion')) $row['religion'] = $faker->randomElement(['Islam','Christianity','Hinduism','Other']);
            if ($this->has('students', 'cast')) $row['cast'] = $faker->word();
            if ($this->has('students', 'blood_group')) $row['blood_group'] = $faker->randomElement(['A+','A-','B+','B-','AB+','AB-','O+','O-']);
            if ($this->has('students', 'photo_path')) $row['photo_path'] = 'default.jpg';
            // merchant_id already set above in $row initialization

            DB::table('students')->insert($row);
        }
    }

    private function seedFeeSummaries($faker): void
    {
        if (!Schema::hasTable('fee_summaries')) return;
        if (DB::table('fee_summaries')->count() > 0) return;

        $studentIds = Schema::hasTable('students') ? DB::table('students')->pluck('id')->all() : [];
        $classIds = Schema::hasTable('classes') ? DB::table('classes')->pluck('id')->all() : [];
        $yearIds = Schema::hasTable('academic_years') ? DB::table('academic_years')->pluck('id')->all() : [];

        foreach ($studentIds as $sid) {
            $total = $faker->numberBetween(2000, 8000);
            $paid = $faker->numberBetween(0, $total);
            $discount = $faker->numberBetween(0, 500);
            $fine = $paid < $total ? $faker->numberBetween(0, 200) : 0;
            $balance = $total - $paid - $discount + $fine;

            DB::table('fee_summaries')->insert([
                'merchant_id' => 'DEFAULT_TENANT',
                'student_id' => $sid,
                'class_id' => !empty($classIds) ? $faker->randomElement($classIds) : null,
                'academic_year_id' => !empty($yearIds) ? $faker->randomElement($yearIds) : null,
                'fee_type' => $faker->randomElement(['tuition','transport','lab']),
                'total_amount' => $total,
                'paid_amount' => $paid,
                'discount_amount' => $discount,
                'fine_amount' => $fine,
                'balance_amount' => $balance,
                'due_date' => $faker->date('Y-m-d', '+1 month'),
                'status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending'),
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}


