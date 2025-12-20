<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // User Management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            
            // Role Management
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
            
            // Permission Management
            'view-permissions',
            'create-permissions',
            'edit-permissions',
            'delete-permissions',
            
            // Product Management
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',
            
            // Order Management
            'view-orders',
            'create-orders',
            'edit-orders',
            'delete-orders',
            
            // Report Access
            'view-reports',
            'generate-reports',
            'export-reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        
        

        // Create Roles
        $superAdmin = Role::create(['name' => 'super-admin']);
        $admin = Role::create(['name' => 'admin']);
        $manager = Role::create(['name' => 'manager']);
        $user = Role::create(['name' => 'user']);

        // Assign Permissions to Roles
        
        // Super Admin gets everything
        $superAdmin->givePermissionTo(Permission::all());

        // Admin gets most permissions except permission management
        $admin->givePermissionTo([
            'view-users', 'create-users', 'edit-users', 'delete-users',
            'view-roles', 'create-roles', 'edit-roles',
            'view-products', 'create-products', 'edit-products', 'delete-products',
            'view-orders', 'create-orders', 'edit-orders', 'delete-orders',
            'view-reports', 'generate-reports', 'export-reports',
        ]);

        // Manager gets product and order management
        $manager->givePermissionTo([
            'view-products', 'create-products', 'edit-products',
            'view-orders', 'create-orders', 'edit-orders',
            'view-reports', 'generate-reports',
        ]);

        // Regular user gets basic permissions
        $user->givePermissionTo([
            'view-products',
            'view-orders', 'create-orders',
        ]);

        // Create Super Admin User
        $superAdminUser = User::create([
            'name' => 'Mudassar Basharat',
            'email' => 'mudasirbasharat17@gmail.com',
            'password' => Hash::make('mud 7866'),
        ]);
        $superAdminUser->assignRole('super-admin');

        // Create Admin User
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $adminUser->assignRole('admin');

        // Create Manager User
        $managerUser = User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('password123'),
        ]);
        $managerUser->assignRole('manager');

        // Create Regular User
        $regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);
        $regularUser->assignRole('user');
    }
} 