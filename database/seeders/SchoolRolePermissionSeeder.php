<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SchoolRolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Clear existing permissions and roles safely
        Permission::truncate();
        Role::truncate();

        // Define all modules with detailed permissions
        $modules = [
            'students' => [
                'permissions' => ['create', 'view', 'edit', 'delete', 'import', 'export', 'profile-access'],
                'description' => 'Student information management'
            ],
            'teachers' => [
                'permissions' => ['create', 'view', 'edit', 'delete', 'import', 'export', 'profile-access'],
                'description' => 'Teacher information management'
            ],
            'parents' => [
                'permissions' => ['view', 'edit', 'contact', 'meetings'],
                'description' => 'Parent information and communication'
            ],
            'classes' => [
                'permissions' => ['create', 'view', 'edit', 'delete', 'assign-students', 'assign-teachers'],
                'description' => 'Class management'
            ],
            'sections' => [
                'permissions' => ['create', 'view', 'edit', 'delete'],
                'description' => 'Section management'
            ],
            'subjects' => [
                'permissions' => ['create', 'view', 'edit', 'delete', 'assign-teachers'],
                'description' => 'Subject management'
            ],
            'attendance' => [
                'permissions' => ['mark', 'view', 'edit', 'reports', 'bulk-import'],
                'description' => 'Attendance management'
            ],
            'grades' => [
                'permissions' => ['create', 'view', 'edit', 'delete', 'publish'],
                'description' => 'Grade and marks management'
            ],
            'exams' => [
                'permissions' => ['create', 'view', 'edit', 'delete', 'schedule', 'results'],
                'description' => 'Examination management'
            ],
            'timetable' => [
                'permissions' => ['create', 'view', 'edit', 'delete', 'publish'],
                'description' => 'Timetable management'
            ],
            'fees' => [
                'permissions' => ['create', 'view', 'edit', 'delete', 'collect', 'reports', 'discounts'],
                'description' => 'Fee management'
            ],
            'library' => [
                'permissions' => ['manage-books', 'issue-books', 'return-books', 'reports'],
                'description' => 'Library management'
            ],
            'transport' => [
                'permissions' => ['manage-routes', 'manage-vehicles', 'track-students', 'reports'],
                'description' => 'Transport management'
            ],
            'hostel' => [
                'permissions' => ['manage-rooms', 'manage-students', 'fees', 'reports'],
                'description' => 'Hostel management'
            ],
            'events' => [
                'permissions' => ['create', 'view', 'edit', 'delete', 'publish'],
                'description' => 'Event management'
            ],
            'notices' => [
                'permissions' => ['create', 'view', 'edit', 'delete', 'publish'],
                'description' => 'Notice board management'
            ],
            'reports' => [
                'permissions' => ['academic', 'financial', 'attendance', 'student-progress', 'custom'],
                'description' => 'Report generation'
            ],
            'settings' => [
                'permissions' => ['general', 'academic', 'financial', 'notification', 'backup'],
                'description' => 'System settings'
            ],
            'users' => [
                'permissions' => ['create', 'view', 'edit', 'delete', 'permissions'],
                'description' => 'User management'
            ],
            'roles' => [
                'permissions' => ['view', 'assign', 'permissions'],
                'description' => 'Role management (limited)'
            ],
            'dashboard' => [
                'permissions' => ['view', 'analytics', 'reports'],
                'description' => 'Dashboard access'
            ],
            'messaging' => [
                'permissions' => ['send', 'receive', 'channels', 'broadcast'],
                'description' => 'Messaging system'
            ],
            'notifications' => [
                'permissions' => ['view', 'create', 'manage', 'settings'],
                'description' => 'Notification management'
            ]
        ];

        // Create permissions
        foreach ($modules as $module => $data) {
            foreach ($data['permissions'] as $action) {
                Permission::create([
                    'name' => "{$action}-{$module}",
                    'guard_name' => 'api',
                    'module' => $module,
                    'action' => $action,
                    'description' => $data['description']
                ]);
            }
        }

        // Define school-specific roles with detailed permissions
        $schoolRoles = [
            'super-admin' => [
                'display_name' => 'Super Administrator',
                'description' => 'Complete system access with all permissions',
                'permissions' => 'all'
            ],
            'principal' => [
                'display_name' => 'Principal',
                'description' => 'School principal with comprehensive management access',
                'permissions' => [
                    'students' => ['view', 'edit', 'profile-access', 'export'],
                    'teachers' => ['view', 'edit', 'profile-access', 'export'],
                    'parents' => ['view', 'contact', 'meetings'],
                    'classes' => ['view', 'edit', 'assign-students', 'assign-teachers'],
                    'attendance' => ['view', 'reports'],
                    'grades' => ['view', 'publish'],
                    'exams' => ['view', 'schedule', 'results'],
                    'timetable' => ['view', 'edit', 'publish'],
                    'fees' => ['view', 'reports'],
                    'events' => ['create', 'view', 'edit', 'delete', 'publish'],
                    'notices' => ['create', 'view', 'edit', 'delete', 'publish'],
                    'reports' => ['academic', 'financial', 'attendance', 'student-progress'],
                    'dashboard' => ['view', 'analytics', 'reports'],
                    'messaging' => ['send', 'receive', 'channels', 'broadcast'],
                    'notifications' => ['view', 'create', 'manage']
                ]
            ],
            'vice-principal' => [
                'display_name' => 'Vice Principal',
                'description' => 'Assistant principal with academic management access',
                'permissions' => [
                    'students' => ['view', 'edit', 'profile-access'],
                    'teachers' => ['view', 'profile-access'],
                    'classes' => ['view', 'edit', 'assign-students'],
                    'attendance' => ['view', 'reports'],
                    'grades' => ['view'],
                    'exams' => ['view', 'schedule'],
                    'timetable' => ['view', 'edit'],
                    'events' => ['view', 'edit'],
                    'notices' => ['create', 'view', 'edit'],
                    'reports' => ['academic', 'attendance', 'student-progress'],
                    'dashboard' => ['view', 'analytics'],
                    'messaging' => ['send', 'receive', 'channels']
                ]
            ],
            'academic-coordinator' => [
                'display_name' => 'Academic Coordinator',
                'description' => 'Academic activities and curriculum coordinator',
                'permissions' => [
                    'students' => ['view', 'edit'],
                    'teachers' => ['view'],
                    'classes' => ['view', 'edit', 'assign-teachers'],
                    'subjects' => ['create', 'view', 'edit', 'assign-teachers'],
                    'attendance' => ['view', 'reports'],
                    'grades' => ['view', 'edit'],
                    'exams' => ['create', 'view', 'edit', 'schedule'],
                    'timetable' => ['create', 'view', 'edit'],
                    'reports' => ['academic', 'student-progress'],
                    'dashboard' => ['view'],
                    'messaging' => ['send', 'receive']
                ]
            ],
            'accountant' => [
                'display_name' => 'Accountant',
                'description' => 'Financial management and fee collection',
                'permissions' => [
                    'students' => ['view'],
                    'fees' => ['create', 'view', 'edit', 'collect', 'reports', 'discounts'],
                    'reports' => ['financial'],
                    'dashboard' => ['view'],
                    'messaging' => ['send', 'receive']
                ]
            ],
            'librarian' => [
                'display_name' => 'Librarian',
                'description' => 'Library management and book operations',
                'permissions' => [
                    'students' => ['view'],
                    'teachers' => ['view'],
                    'library' => ['manage-books', 'issue-books', 'return-books', 'reports'],
                    'messaging' => ['send', 'receive']
                ]
            ],
            'teacher' => [
                'display_name' => 'Teacher',
                'description' => 'Teaching staff with classroom management',
                'permissions' => [
                    'students' => ['view', 'profile-access'],
                    'classes' => ['view'],
                    'subjects' => ['view'],
                    'attendance' => ['mark', 'view'],
                    'grades' => ['create', 'view', 'edit'],
                    'timetable' => ['view'],
                    'messaging' => ['send', 'receive'],
                    'dashboard' => ['view']
                ]
            ],
            'class-teacher' => [
                'display_name' => 'Class Teacher',
                'description' => 'Teacher with additional class management responsibilities',
                'permissions' => [
                    'students' => ['view', 'edit', 'profile-access'],
                    'parents' => ['view', 'contact'],
                    'classes' => ['view', 'edit'],
                    'attendance' => ['mark', 'view', 'edit'],
                    'grades' => ['create', 'view', 'edit'],
                    'timetable' => ['view'],
                    'notices' => ['create', 'view'],
                    'messaging' => ['send', 'receive', 'channels'],
                    'dashboard' => ['view']
                ]
            ],
            'transport-manager' => [
                'display_name' => 'Transport Manager',
                'description' => 'School transport and vehicle management',
                'permissions' => [
                    'students' => ['view'],
                    'transport' => ['manage-routes', 'manage-vehicles', 'track-students', 'reports'],
                    'messaging' => ['send', 'receive'],
                    'dashboard' => ['view']
                ]
            ],
            'hostel-warden' => [
                'display_name' => 'Hostel Warden',
                'description' => 'Hostel management and student accommodation',
                'permissions' => [
                    'students' => ['view'],
                    'hostel' => ['manage-rooms', 'manage-students', 'fees', 'reports'],
                    'messaging' => ['send', 'receive'],
                    'dashboard' => ['view']
                ]
            ],
            'receptionist' => [
                'display_name' => 'Receptionist',
                'description' => 'Front desk operations and visitor management',
                'permissions' => [
                    'students' => ['view'],
                    'teachers' => ['view'],
                    'parents' => ['view', 'contact'],
                    'notices' => ['view'],
                    'events' => ['view'],
                    'messaging' => ['send', 'receive'],
                    'dashboard' => ['view']
                ]
            ]
        ];

        // Create roles
        foreach ($schoolRoles as $roleName => $roleData) {
            $role = Role::create([
                'name' => $roleName,
                'guard_name' => 'api',
                'display_name' => $roleData['display_name'],
                'description' => $roleData['description']
            ]);

            // Assign permissions to role
            if ($roleData['permissions'] === 'all') {
                $role->givePermissionTo(Permission::all());
            } else {
                $permissionsToAssign = [];
                foreach ($roleData['permissions'] as $module => $actions) {
                    foreach ($actions as $action) {
                        $permissionsToAssign[] = "{$action}-{$module}";
                    }
                }
                $role->givePermissionTo($permissionsToAssign);
            }
        }

        echo "School role and permission system created successfully!\n";
        echo "Created " . Permission::count() . " permissions and " . Role::count() . " roles.\n";
    }
}
