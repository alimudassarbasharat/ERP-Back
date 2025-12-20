<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Define all modules and their actions
        $modules = [
            'students' => ['create', 'read', 'update', 'delete', 'export', 'import'],
            'teachers' => ['create', 'read', 'update', 'delete', 'export', 'import'],
            'classes' => ['create', 'read', 'update', 'delete'],
            'sections' => ['create', 'read', 'update', 'delete'],
            'subjects' => ['create', 'read', 'update', 'delete'],
            'admins' => ['create', 'read', 'update', 'delete'],
            'roles' => ['create', 'read', 'update', 'delete', 'assign'],
            'permissions' => ['create', 'read', 'update', 'delete', 'assign'],
            'attendance' => ['create', 'read', 'update', 'delete', 'reports'],
            'exams' => ['create', 'read', 'update', 'delete'],
            'results' => ['create', 'read', 'update', 'delete', 'publish'],
            'events' => ['create', 'read', 'update', 'delete'],
            'fees' => ['create', 'read', 'update', 'delete', 'collect'],
            'reports' => ['generate', 'view', 'export', 'print'],
            'messaging' => ['create', 'read', 'update', 'delete', 'channels'],
            'settings' => ['read', 'update'],
            'dashboard' => ['view', 'analytics'],
            'certificates' => ['generate', 'print', 'download'],
            'id-cards' => ['generate', 'print', 'download'],
            'fee-challans' => ['generate', 'print', 'download'],
            'sessions' => ['create', 'read', 'update', 'delete'],
            'departments' => ['create', 'read', 'update', 'delete'],
        ];

        // Create permissions
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action}-{$module}",
                    'guard_name' => 'api'
                ]);
            }
        }

        // Create admin types/roles
        $adminTypes = [
            'super-admin' => 'Full system access with all permissions',
            'principal' => 'School principal with management access',
            'vice-principal' => 'Vice principal with limited management access',
            'admin-manager' => 'Administrative manager',
            'academic-coordinator' => 'Academic activities coordinator',
            'finance-manager' => 'Finance and fee management',
            'hr-manager' => 'Human resources management',
            'it-admin' => 'IT and system administration',
            'receptionist' => 'Front desk and basic operations',
            'data-entry' => 'Data entry and basic CRUD operations'
        ];

        foreach ($adminTypes as $type => $description) {
            Role::firstOrCreate([
                'name' => $type,
                'guard_name' => 'api'
            ]);
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        echo "Permissions and roles created successfully!\n";
    }

    private function assignPermissionsToRoles()
    {
        // Super Admin - All permissions
        $superAdmin = Role::findByName('super-admin');
        $superAdmin->givePermissionTo(Permission::all());

        // Principal - Almost all permissions except IT admin tasks
        $principal = Role::findByName('principal');
        $principalPermissions = Permission::where('name', 'not like', '%settings%')
            ->where('name', 'not like', '%permissions%')
            ->get();
        $principal->givePermissionTo($principalPermissions);

        // Vice Principal - Management permissions without admin management
        $vicePrincipal = Role::findByName('vice-principal');
        $vicePrincipalPermissions = Permission::where('name', 'not like', '%admins%')
            ->where('name', 'not like', '%roles%')
            ->where('name', 'not like', '%permissions%')
            ->where('name', 'not like', '%settings%')
            ->get();
        $vicePrincipal->givePermissionTo($vicePrincipalPermissions);

        // Admin Manager - User and role management
        $adminManager = Role::findByName('admin-manager');
        $adminManager->givePermissionTo([
            'create-admins', 'read-admins', 'update-admins', 'delete-admins',
            'read-roles', 'assign-roles',
            'read-permissions', 'assign-permissions',
            'view-dashboard'
        ]);

        // Academic Coordinator - Academic modules
        $academicCoordinator = Role::findByName('academic-coordinator');
        $academicCoordinator->givePermissionTo([
            'create-students', 'read-students', 'update-students',
            'create-teachers', 'read-teachers', 'update-teachers',
            'create-classes', 'read-classes', 'update-classes',
            'create-sections', 'read-sections', 'update-sections',
            'create-subjects', 'read-subjects', 'update-subjects',
            'create-exams', 'read-exams', 'update-exams',
            'create-results', 'read-results', 'update-results',
            'create-attendance', 'read-attendance', 'update-attendance',
            'view-dashboard', 'generate-reports', 'view-reports'
        ]);

        // Finance Manager - Fee and financial modules
        $financeManager = Role::findByName('finance-manager');
        $financeManager->givePermissionTo([
            'create-fees', 'read-fees', 'update-fees', 'collect-fees',
            'generate-fee-challans', 'print-fee-challans',
            'generate-reports', 'view-reports', 'export-reports',
            'view-dashboard', 'read-students'
        ]);

        // HR Manager - Staff management
        $hrManager = Role::findByName('hr-manager');
        $hrManager->givePermissionTo([
            'create-teachers', 'read-teachers', 'update-teachers', 'delete-teachers',
            'create-admins', 'read-admins', 'update-admins',
            'read-departments', 'create-departments', 'update-departments',
            'generate-reports', 'view-reports', 'view-dashboard'
        ]);

        // IT Admin - System and technical management
        $itAdmin = Role::findByName('it-admin');
        $itAdmin->givePermissionTo([
            'read-settings', 'update-settings',
            'create-roles', 'read-roles', 'update-roles',
            'read-permissions', 'assign-permissions',
            'create-messaging', 'read-messaging', 'update-messaging', 'channels-messaging',
            'view-dashboard', 'analytics-dashboard'
        ]);

        // Receptionist - Basic operations
        $receptionist = Role::findByName('receptionist');
        $receptionist->givePermissionTo([
            'read-students', 'read-teachers',
            'create-events', 'read-events', 'update-events',
            'read-messaging', 'create-messaging',
            'view-dashboard'
        ]);

        // Data Entry - Basic CRUD for main entities
        $dataEntry = Role::findByName('data-entry');
        $dataEntry->givePermissionTo([
            'create-students', 'read-students', 'update-students',
            'create-attendance', 'read-attendance', 'update-attendance',
            'read-classes', 'read-sections', 'read-subjects',
            'view-dashboard'
        ]);
    }
}
