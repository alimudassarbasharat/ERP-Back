<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // Direct seeding without calling other seeders to avoid mbstring issues

        // 1. Create roles
        $roles = [
            ['id' => 1, 'name' => 'super-admin', 'guard_name' => 'web', 'merchant_id' => 'DEFAULT_TENANT'],
            ['id' => 2, 'name' => 'admin', 'guard_name' => 'web', 'merchant_id' => 'DEFAULT_TENANT'],
            ['id' => 3, 'name' => 'teacher', 'guard_name' => 'web', 'merchant_id' => 'DEFAULT_TENANT'],
            ['id' => 4, 'name' => 'student', 'guard_name' => 'web', 'merchant_id' => 'DEFAULT_TENANT'],
        ];

        foreach ($roles as $role) {
            DB::statement("INSERT INTO roles (id, name, guard_name, merchant_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING", [
                $role['id'], $role['name'], $role['guard_name'], $role['merchant_id'], now(), now()
            ]);
        }

        // 2. Create permissions
        $permissions = [
            ['id' => 1, 'name' => 'manage-users', 'guard_name' => 'web', 'merchant_id' => 'DEFAULT_TENANT'],
            ['id' => 2, 'name' => 'manage-students', 'guard_name' => 'web', 'merchant_id' => 'DEFAULT_TENANT'],
            ['id' => 3, 'name' => 'view-reports', 'guard_name' => 'web', 'merchant_id' => 'DEFAULT_TENANT'],
        ];

        foreach ($permissions as $permission) {
            DB::statement("INSERT INTO permissions (id, name, guard_name, merchant_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING", [
                $permission['id'], $permission['name'], $permission['guard_name'], $permission['merchant_id'], now(), now()
            ]);
        }

        // 3. Create departments
        $departments = [
            ['id' => 1, 'name' => 'Computer Science', 'code' => 'CS', 'status' => true, 'merchant_id' => 'DEFAULT_TENANT'],
            ['id' => 2, 'name' => 'Mathematics', 'code' => 'MATH', 'status' => true, 'merchant_id' => 'DEFAULT_TENANT'],
            ['id' => 3, 'name' => 'English', 'code' => 'ENG', 'status' => true, 'merchant_id' => 'DEFAULT_TENANT'],
        ];

        foreach ($departments as $dept) {
            DB::statement("INSERT INTO departments (id, name, code, status, merchant_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING", [
                $dept['id'], $dept['name'], $dept['code'], $dept['status'], $dept['merchant_id'], now(), now()
            ]);
        }

        // 4. Create academic years
        $academicYears = [
            ['id' => 1, 'name' => '2024-2025', 'start_date' => '2024-04-01', 'end_date' => '2025-03-31', 'is_active' => true, 'merchant_id' => 'DEFAULT_TENANT'],
            ['id' => 2, 'name' => '2025-2026', 'start_date' => '2025-04-01', 'end_date' => '2026-03-31', 'is_active' => false, 'merchant_id' => 'DEFAULT_TENANT'],
        ];

        foreach ($academicYears as $year) {
            DB::statement("INSERT INTO academic_years (id, name, start_date, end_date, is_active, merchant_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING", [
                $year['id'], $year['name'], $year['start_date'], $year['end_date'], $year['is_active'], $year['merchant_id'], now(), now()
            ]);
        }

        // 5. Create users
        DB::statement("INSERT INTO users (name, email, password, status, merchant_id, created_at, updated_at) VALUES ('Test User', 'user@test.com', ?, 'active', 'DEFAULT_TENANT', ?, ?) ON CONFLICT (email) DO NOTHING", [
            Hash::make('password'), now(), now()
        ]);

        DB::statement("INSERT INTO users (name, email, password, status, merchant_id, created_at, updated_at) VALUES ('Teacher User', 'teacher@test.com', ?, 'active', 'DEFAULT_TENANT', ?, ?) ON CONFLICT (email) DO NOTHING", [
            Hash::make('password'), now(), now()
        ]);

        // 6. Create admins
        DB::statement("INSERT INTO admins (name, email, password, role_id, status, merchant_id, created_at, updated_at) VALUES ('Super Admin', 'superadmin@test.com', ?, 1, 'active', 'DEFAULT_TENANT', ?, ?) ON CONFLICT (email) DO NOTHING", [
            Hash::make('password'), now(), now()
        ]);

        DB::statement("INSERT INTO admins (name, email, password, role_id, status, merchant_id, created_at, updated_at) VALUES ('Admin User', 'admin@test.com', ?, 2, 'active', 'ADMIN123', ?, ?) ON CONFLICT (email) DO NOTHING", [
            Hash::make('password'), now(), now()
        ]);

		// 7. Ensure Passport clients exist (prevents "Personal access client not found" at runtime)
		if (Schema::hasTable('oauth_clients')) {
			$personalAccessClientExists = DB::table('oauth_clients')->where('personal_access_client', 1)->exists();
			$passwordGrantClientExists = DB::table('oauth_clients')->where('password_client', 1)->exists();

			/** @var ClientRepository $clientRepository */
			$clientRepository = app(ClientRepository::class);
			$redirectUrl = config('app.url') ?: 'http://localhost';

			if (!$personalAccessClientExists) {
				$clientRepository->createPersonalAccessClient(null, 'Default Personal Access Client', $redirectUrl);
			}

			if (!$passwordGrantClientExists) {
				$provider = config('auth.guards.api.provider');
				try {
					$clientRepository->createPasswordGrantClient(null, 'Default Password Grant Client', $redirectUrl, $provider);
				} catch (\Throwable $exception) {
					// Fallback for older Passport signatures without provider argument
					$clientRepository->createPasswordGrantClient(null, 'Default Password Grant Client', $redirectUrl);
				}
			}
		}

        // 8. Seed demo relational data for common tables (idempotent)
        $this->call(DemoDataSeeder::class);

        // 9. Seed exam management demo data (optional, run separately if needed)
        // $this->call(ExamManagementDemoSeeder::class);
    }
}
