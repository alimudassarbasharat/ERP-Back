<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompleteSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        echo "🚀 Starting complete database setup...\n";

        // 1. Create roles and permissions
        echo "1️⃣ Creating roles and permissions...\n";
        $roles = [
            ['id' => 1, 'name' => 'super-admin', 'guard_name' => 'web'],
            ['id' => 2, 'name' => 'admin', 'guard_name' => 'web'],
            ['id' => 3, 'name' => 'teacher', 'guard_name' => 'web'],
            ['id' => 4, 'name' => 'student', 'guard_name' => 'web'],
        ];

        foreach ($roles as $role) {
            DB::statement("INSERT INTO roles (id, name, guard_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING", [
                $role['id'], $role['name'], $role['guard_name'], now(), now()
            ]);
        }

        $permissions = [
            ['id' => 1, 'name' => 'manage-users', 'guard_name' => 'web'],
            ['id' => 2, 'name' => 'manage-students', 'guard_name' => 'web'],
            ['id' => 3, 'name' => 'view-reports', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            DB::statement("INSERT INTO permissions (id, name, guard_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING", [
                $permission['id'], $permission['name'], $permission['guard_name'], now(), now()
            ]);
        }

        // 2. Create departments
        echo "2️⃣ Creating departments...\n";
        $departments = [
            ['id' => 1, 'name' => 'Computer Science', 'code' => 'CS', 'status' => true],
            ['id' => 2, 'name' => 'Mathematics', 'code' => 'MATH', 'status' => true],
            ['id' => 3, 'name' => 'English', 'code' => 'ENG', 'status' => true],
        ];

        foreach ($departments as $dept) {
            DB::statement("INSERT INTO departments (id, name, code, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING", [
                $dept['id'], $dept['name'], $dept['code'], $dept['status'], now(), now()
            ]);
        }

        // 3. Create academic years
        echo "3️⃣ Creating academic years...\n";
        $academicYears = [
            ['id' => 1, 'name' => '2024-2025', 'start_date' => '2024-04-01', 'end_date' => '2025-03-31', 'is_active' => true],
            ['id' => 2, 'name' => '2025-2026', 'start_date' => '2025-04-01', 'end_date' => '2026-03-31', 'is_active' => false],
        ];

        foreach ($academicYears as $year) {
            DB::statement("INSERT INTO academic_years (id, name, start_date, end_date, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING", [
                $year['id'], $year['name'], $year['start_date'], $year['end_date'], $year['is_active'], now(), now()
            ]);
        }

        // 4. Create basic users
        echo "4️⃣ Creating users...\n";
        DB::statement("INSERT INTO users (name, email, password, status, created_at, updated_at) VALUES ('Test User', 'user@test.com', ?, 'active', ?, ?) ON CONFLICT (email) DO NOTHING", [
            Hash::make('password'), now(), now()
        ]);

        DB::statement("INSERT INTO users (name, email, password, status, created_at, updated_at) VALUES ('Teacher User', 'teacher@test.com', ?, 'active', ?, ?) ON CONFLICT (email) DO NOTHING", [
            Hash::make('password'), now(), now()
        ]);

        // 5. Create admin users
        echo "5️⃣ Creating admin users...\n";
        DB::statement("INSERT INTO admins (name, email, password, role_id, status, merchant_id, created_at, updated_at) VALUES ('Super Admin', 'superadmin@test.com', ?, 1, 'active', 'SUPER123', ?, ?) ON CONFLICT (email) DO NOTHING", [
            Hash::make('password'), now(), now()
        ]);

        DB::statement("INSERT INTO admins (name, email, password, role_id, status, merchant_id, created_at, updated_at) VALUES ('Admin User', 'admin@test.com', ?, 2, 'active', 'ADMIN123', ?, ?) ON CONFLICT (email) DO NOTHING", [
            Hash::make('password'), now(), now()
        ]);

        echo "\n✅ Database setup completed successfully!\n";
        echo "═══════════════════════════════════\n";
        echo "🔐 Login Credentials:\n";
        echo "───────────────────────────────────\n";
        echo "👑 Super Admin: superadmin@test.com | password\n";
        echo "👤 Admin: admin@test.com | password\n";
        echo "🧑‍🏫 Teacher: teacher@test.com | password\n";
        echo "👨‍🎓 User: user@test.com | password\n";
        echo "═══════════════════════════════════\n";
        echo "🎉 Your ERP system is ready to use!\n\n";
    }
}
