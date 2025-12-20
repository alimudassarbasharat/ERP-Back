<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create basic roles
        $roles = [
            ['id' => 1, 'name' => 'super-admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'teacher', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'student', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'parent', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore($role);
        }

        // Create basic permissions
        $permissions = [
            ['id' => 1, 'name' => 'manage-users', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'manage-students', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'manage-teachers', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'view-reports', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'manage-attendance', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore($permission);
        }

        $this->command->info('Roles and permissions seeded successfully!');
    }
}