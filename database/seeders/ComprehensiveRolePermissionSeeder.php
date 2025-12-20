<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Schema;

class ComprehensiveRolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Clear existing permissions and roles to avoid duplicates
        Permission::truncate();
        Role::truncate();
        
        // Define all permissions grouped by module with comprehensive coverage based on frontend analysis
        $permissions = [
            // Dashboard permissions
            'dashboard' => [
                'view-dashboard'
            ],
            
            // Admin Management permissions
            'admin' => [
                'view-admins',
                'create-admins',
                'edit-admins',
                'delete-admins',
                'view-sub-admins',
                'create-sub-admins',
                'edit-sub-admins',
                'delete-sub-admins',
                'assign-roles',
                'manage-permissions',
                'view-admin-management',
                'access-role-permission-management',
                'view-users',
                'manage-users'
            ],
            
            // Faculty Management permissions
            'faculty' => [
                'view-teachers',
                'create-teachers',
                'edit-teachers',
                'delete-teachers',
                'view-teacher-details',
                'view-faculty-management',
                'add-faculty',
                'edit-faculty'
            ],
            
            // Student Management permissions
            'students' => [
                'view-students',
                'create-students',
                'edit-students',
                'delete-students',
                'view-student-details',
                'manage-student-attendance',
                'generate-student-cards',
                'student-registration',
                'view-student-management',
                'access-student-attendance'
            ],
            
            // Classes Management permissions
            'classes' => [
                'view-classes',
                'create-classes',
                'edit-classes',
                'delete-classes',
                'view-class-sections',
                'create-class-sections',
                'edit-class-sections',
                'delete-class-sections',
                'view-class-subjects',
                'create-class-subjects',
                'edit-class-subjects',
                'delete-class-subjects',
                'view-class-management'
            ],
            
            // Exams Management permissions
            'exams' => [
                'view-exams',
                'create-exams',
                'edit-exams',
                'delete-exams',
                'manage-marksheets',
                'view-marksheets',
                'edit-marksheets',
                'add-marksheet',
                'add-marksheet-subjectwise',
                'view-mark-reports',
                'manage-award-lists',
                'view-award-lists',
                'view-exam-management'
            ],
            
            // Fee Management permissions
            'fees' => [
                'view-fees',
                'create-fees',
                'edit-fees',
                'delete-fees',
                'collect-fees',
                'generate-fee-challans',
                'process-fee-payments',
                'view-fee-analytics',
                'manage-fee',
                'pay-fee',
                'view-fee-management',
                'access-fee-collection',
                'access-fee-analytics'
            ],
            
            // Finance Management permissions
            'finance' => [
                'view-transactions',
                'create-transactions',
                'edit-transactions',
                'delete-transactions',
                'view-financial-reports',
                'manage-financial-settings',
                'view-finance-management'
            ],
            
            // Reports permissions
            'reports' => [
                'view-student-reports',
                'view-teacher-reports',
                'view-class-wise-reports',
                'view-family-wise-reports',
                'export-reports',
                'access-reports',
                'generate-reports'
            ],
            
            // Events Management permissions
            'events' => [
                'view-events',
                'create-events',
                'edit-events',
                'delete-events',
                'view-event-calendar',
                'view-event-list',
                'generate-events',
                'view-event-management'
            ],
            
            // Facility Management permissions
            'facility' => [
                'view-facilities',
                'create-facilities',
                'edit-facilities',
                'delete-facilities',
                'manage-facility-maintenance',
                'view-facility-management',
                'access-facility-maintenance'
            ],
            
            // Settings permissions
            'settings' => [
                'view-general-settings',
                'edit-general-settings',
                'view-notification-settings',
                'edit-notification-settings',
                'access-settings'
            ],
            
            // Messaging permissions
            'messaging' => [
                'access-messaging',
                'send-messages',
                'create-channels',
                'manage-slack-integration',
                'view-team-chat',
                'access-slack'
            ],
            
            // Profile permissions
            'profile' => [
                'view-profile',
                'edit-profile',
                'access-profile'
            ],
            
            // Template permissions
            'templates' => [
                'view-templates',
                'access-template-gallery',
                'use-templates'
            ]
        ];

        // Create permissions
        foreach ($permissions as $module => $modulePermissions) {
            foreach ($modulePermissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web'
                ]);
            }
        }

        // Define comprehensive roles with school hierarchy
        $roles = [
            'super-admin' => [
                'display_name' => 'Super Administrator',
                'description' => 'Has complete access to all system features and settings',
                'permissions' => 'all'
            ],
            'principal' => [
                'display_name' => 'Principal',
                'description' => 'School principal with administrative oversight',
                'permissions' => [
                    // Dashboard
                    'view-dashboard',
                    // Admin Management (limited)
                    'view-admins', 'view-sub-admins', 'assign-roles', 'view-admin-management',
                    // Faculty Management (full)
                    'view-teachers', 'create-teachers', 'edit-teachers', 'delete-teachers', 'view-teacher-details', 'view-faculty-management', 'add-faculty', 'edit-faculty',
                    // Student Management (full)
                    'view-students', 'create-students', 'edit-students', 'delete-students', 'view-student-details', 'manage-student-attendance', 'generate-student-cards', 'student-registration', 'view-student-management', 'access-student-attendance',
                    // Classes Management (full)
                    'view-classes', 'create-classes', 'edit-classes', 'delete-classes', 'view-class-sections', 'create-class-sections', 'edit-class-sections', 'delete-class-sections', 'view-class-subjects', 'create-class-subjects', 'edit-class-subjects', 'delete-class-subjects', 'view-class-management',
                    // Exams Management (full)
                    'view-exams', 'create-exams', 'edit-exams', 'delete-exams', 'manage-marksheets', 'view-marksheets', 'edit-marksheets', 'add-marksheet', 'add-marksheet-subjectwise', 'view-mark-reports', 'manage-award-lists', 'view-award-lists', 'view-exam-management',
                    // Fee Management (full)
                    'view-fees', 'create-fees', 'edit-fees', 'collect-fees', 'view-fee-analytics', 'manage-fee', 'view-fee-management', 'access-fee-collection', 'access-fee-analytics',
                    // Finance Management
                    'view-transactions', 'view-financial-reports', 'view-finance-management',
                    // Reports (full)
                    'view-student-reports', 'view-teacher-reports', 'view-class-wise-reports', 'view-family-wise-reports', 'export-reports', 'access-reports', 'generate-reports',
                    // Events Management (full)
                    'view-events', 'create-events', 'edit-events', 'delete-events', 'view-event-calendar', 'view-event-list', 'generate-events', 'view-event-management',
                    // Facility Management (full)
                    'view-facilities', 'create-facilities', 'edit-facilities', 'delete-facilities', 'manage-facility-maintenance', 'view-facility-management', 'access-facility-maintenance',
                    // Settings (full)
                    'view-general-settings', 'edit-general-settings', 'view-notification-settings', 'edit-notification-settings', 'access-settings',
                    // Messaging (full)
                    'access-messaging', 'send-messages', 'create-channels', 'manage-slack-integration', 'view-team-chat', 'access-slack',
                    // Profile
                    'view-profile', 'edit-profile', 'access-profile',
                    // Templates
                    'view-templates', 'access-template-gallery', 'use-templates'
                ]
            ],
            'vice-principal' => [
                'display_name' => 'Vice Principal',
                'description' => 'Assistant to principal with limited administrative access',
                'permissions' => [
                    // Dashboard
                    'view-dashboard',
                    // Faculty Management
                    'view-teachers', 'create-teachers', 'edit-teachers', 'view-teacher-details', 'view-faculty-management', 'add-faculty', 'edit-faculty',
                    // Student Management
                    'view-students', 'create-students', 'edit-students', 'view-student-details', 'manage-student-attendance', 'generate-student-cards', 'student-registration', 'view-student-management', 'access-student-attendance',
                    // Classes Management
                    'view-classes', 'create-classes', 'edit-classes', 'view-class-sections', 'create-class-sections', 'edit-class-sections', 'view-class-subjects', 'create-class-subjects', 'edit-class-subjects', 'view-class-management',
                    // Exams Management
                    'view-exams', 'create-exams', 'edit-exams', 'manage-marksheets', 'view-marksheets', 'edit-marksheets', 'add-marksheet', 'add-marksheet-subjectwise', 'view-mark-reports', 'manage-award-lists', 'view-award-lists', 'view-exam-management',
                    // Fee Management (limited)
                    'view-fees', 'collect-fees', 'view-fee-analytics', 'view-fee-management', 'access-fee-collection', 'access-fee-analytics',
                    // Reports
                    'view-student-reports', 'view-teacher-reports', 'view-class-wise-reports', 'view-family-wise-reports', 'export-reports', 'access-reports', 'generate-reports',
                    // Events Management
                    'view-events', 'create-events', 'edit-events', 'view-event-calendar', 'view-event-list', 'generate-events', 'view-event-management',
                    // Facility Management (limited)
                    'view-facilities', 'manage-facility-maintenance', 'view-facility-management', 'access-facility-maintenance',
                    // Messaging
                    'access-messaging', 'send-messages', 'view-team-chat', 'access-slack',
                    // Profile
                    'view-profile', 'edit-profile', 'access-profile',
                    // Templates
                    'view-templates', 'access-template-gallery', 'use-templates'
                ]
            ],
            'teacher' => [
                'display_name' => 'Teacher',
                'description' => 'Teaching staff with classroom management access',
                'permissions' => [
                    // Dashboard
                    'view-dashboard',
                    // Student Management (limited)
                    'view-students', 'view-student-details', 'manage-student-attendance', 'view-student-management', 'access-student-attendance',
                    // Classes Management (view only)
                    'view-classes', 'view-class-sections', 'view-class-subjects', 'view-class-management',
                    // Exams Management
                    'view-exams', 'manage-marksheets', 'view-marksheets', 'edit-marksheets', 'add-marksheet', 'add-marksheet-subjectwise', 'view-mark-reports', 'view-exam-management',
                    // Reports
                    'view-student-reports', 'view-class-wise-reports', 'access-reports',
                    // Events Management (view only)
                    'view-events', 'view-event-calendar', 'view-event-list', 'view-event-management',
                    // Messaging
                    'access-messaging', 'send-messages', 'view-team-chat', 'access-slack',
                    // Profile
                    'view-profile', 'edit-profile', 'access-profile',
                    // Templates
                    'view-templates', 'access-template-gallery', 'use-templates'
                ]
            ],
            'accountant' => [
                'display_name' => 'Accountant',
                'description' => 'Financial management and fee collection specialist',
                'permissions' => [
                    // Dashboard
                    'view-dashboard',
                    // Student Management (view only for fee purposes)
                    'view-students', 'view-student-details', 'view-student-management',
                    // Fee Management (full)
                    'view-fees', 'create-fees', 'edit-fees', 'collect-fees', 'generate-fee-challans', 'process-fee-payments', 'view-fee-analytics', 'manage-fee', 'pay-fee', 'view-fee-management', 'access-fee-collection', 'access-fee-analytics',
                    // Finance Management (full)
                    'view-transactions', 'create-transactions', 'edit-transactions', 'view-financial-reports', 'manage-financial-settings', 'view-finance-management',
                    // Reports (financial focus)
                    'view-student-reports', 'export-reports', 'access-reports', 'generate-reports',
                    // Messaging
                    'access-messaging', 'send-messages', 'view-team-chat', 'access-slack',
                    // Profile
                    'view-profile', 'edit-profile', 'access-profile'
                ]
            ],
            'librarian' => [
                'display_name' => 'Librarian',
                'description' => 'Library management and student record access',
                'permissions' => [
                    // Dashboard
                    'view-dashboard',
                    // Student Management (view only)
                    'view-students', 'view-student-details', 'view-student-management',
                    // Faculty Management (view only)
                    'view-teachers', 'view-teacher-details', 'view-faculty-management',
                    // Reports
                    'view-student-reports', 'view-teacher-reports', 'access-reports',
                    // Events Management (view only)
                    'view-events', 'view-event-calendar', 'view-event-list', 'view-event-management',
                    // Messaging
                    'access-messaging', 'send-messages', 'view-team-chat', 'access-slack',
                    // Profile
                    'view-profile', 'edit-profile', 'access-profile',
                    // Templates
                    'view-templates', 'access-template-gallery', 'use-templates'
                ]
            ],
            'student' => [
                'display_name' => 'Student',
                'description' => 'Student with limited access to personal information',
                'permissions' => [
                    // Dashboard
                    'view-dashboard',
                    // Student Management (own data only - to be restricted by policies)
                    'view-student-details',
                    // Exams Management (view only)
                    'view-marksheets', 'view-mark-reports',
                    // Events Management (view only)
                    'view-events', 'view-event-calendar', 'view-event-list',
                    // Messaging
                    'access-messaging', 'send-messages', 'view-team-chat', 'access-slack',
                    // Profile
                    'view-profile', 'edit-profile', 'access-profile'
                ]
            ],
            'parent' => [
                'display_name' => 'Parent',
                'description' => 'Parent with access to child information',
                'permissions' => [
                    // Dashboard
                    'view-dashboard',
                    // Student Management (child data only - to be restricted by policies)
                    'view-student-details',
                    // Exams Management (child data only)
                    'view-marksheets', 'view-mark-reports',
                    // Fee Management (child fees only)
                    'view-fees', 'process-fee-payments', 'pay-fee',
                    // Events Management (view only)
                    'view-events', 'view-event-calendar', 'view-event-list',
                    // Messaging
                    'access-messaging', 'send-messages', 'view-team-chat', 'access-slack',
                    // Profile
                    'view-profile', 'edit-profile', 'access-profile'
                ]
            ],
            'it-admin' => [
                'display_name' => 'IT Administrator',
                'description' => 'Technical support and system maintenance',
                'permissions' => [
                    // Dashboard
                    'view-dashboard',
                    // Admin Management (limited)
                    'view-admins', 'view-sub-admins', 'view-admin-management',
                    // Faculty Management
                    'view-teachers', 'view-teacher-details', 'view-faculty-management',
                    // Student Management
                    'view-students', 'view-student-details', 'view-student-management',
                    // Settings (full)
                    'view-general-settings', 'edit-general-settings', 'view-notification-settings', 'edit-notification-settings', 'access-settings',
                    // Messaging (full technical access)
                    'access-messaging', 'send-messages', 'create-channels', 'manage-slack-integration', 'view-team-chat', 'access-slack',
                    // Profile
                    'view-profile', 'edit-profile', 'access-profile',
                    // Templates
                    'view-templates', 'access-template-gallery', 'use-templates'
                ]
            ],
            'receptionist' => [
                'display_name' => 'Receptionist',
                'description' => 'Front desk operations and visitor management',
                'permissions' => [
                    // Dashboard
                    'view-dashboard',
                    // Student Management (limited)
                    'view-students', 'view-student-details', 'view-student-management',
                    // Faculty Management (view only)
                    'view-teachers', 'view-teacher-details', 'view-faculty-management',
                    // Events Management (view only)
                    'view-events', 'view-event-calendar', 'view-event-list', 'view-event-management',
                    // Messaging
                    'access-messaging', 'send-messages', 'view-team-chat', 'access-slack',
                    // Profile
                    'view-profile', 'edit-profile', 'access-profile'
                ]
            ],
            'data-entry' => [
                'display_name' => 'Data Entry Operator',
                'description' => 'Data input and basic record management',
                'permissions' => [
                    // Dashboard
                    'view-dashboard',
                    // Student Management
                    'view-students', 'create-students', 'edit-students', 'view-student-details', 'student-registration', 'view-student-management',
                    // Faculty Management
                    'view-teachers', 'create-teachers', 'edit-teachers', 'view-teacher-details', 'view-faculty-management', 'add-faculty', 'edit-faculty',
                    // Classes Management (view mostly)
                    'view-classes', 'view-class-sections', 'view-class-subjects', 'view-class-management',
                    // Events Management (basic)
                    'view-events', 'create-events', 'edit-events', 'view-event-calendar', 'view-event-list', 'generate-events', 'view-event-management',
                    // Profile
                    'view-profile', 'edit-profile', 'access-profile',
                    // Templates
                    'view-templates', 'access-template-gallery', 'use-templates'
                ]
            ],
            'admin' => [
                'display_name' => 'Administrator',
                'description' => 'General administrator with broad access but cannot create new roles',
                'permissions' => [
                    // Dashboard
                    'view-dashboard',
                    // Admin Management (limited - cannot create new roles)
                    'view-admins', 'create-admins', 'edit-admins', 'view-sub-admins', 'create-sub-admins', 'edit-sub-admins', 'assign-roles', 'view-admin-management', 'access-role-permission-management',
                    // Faculty Management (full)
                    'view-teachers', 'create-teachers', 'edit-teachers', 'delete-teachers', 'view-teacher-details', 'view-faculty-management', 'add-faculty', 'edit-faculty',
                    // Student Management (full)
                    'view-students', 'create-students', 'edit-students', 'delete-students', 'view-student-details', 'manage-student-attendance', 'generate-student-cards', 'student-registration', 'view-student-management', 'access-student-attendance',
                    // Classes Management (full)
                    'view-classes', 'create-classes', 'edit-classes', 'delete-classes', 'view-class-sections', 'create-class-sections', 'edit-class-sections', 'delete-class-sections', 'view-class-subjects', 'create-class-subjects', 'edit-class-subjects', 'delete-class-subjects', 'view-class-management',
                    // Exams Management (full)
                    'view-exams', 'create-exams', 'edit-exams', 'delete-exams', 'manage-marksheets', 'view-marksheets', 'edit-marksheets', 'add-marksheet', 'add-marksheet-subjectwise', 'view-mark-reports', 'manage-award-lists', 'view-award-lists', 'view-exam-management',
                    // Fee Management (full)
                    'view-fees', 'create-fees', 'edit-fees', 'delete-fees', 'collect-fees', 'generate-fee-challans', 'process-fee-payments', 'view-fee-analytics', 'manage-fee', 'pay-fee', 'view-fee-management', 'access-fee-collection', 'access-fee-analytics',
                    // Finance Management (full)
                    'view-transactions', 'create-transactions', 'edit-transactions', 'delete-transactions', 'view-financial-reports', 'manage-financial-settings', 'view-finance-management',
                    // Reports (full)
                    'view-student-reports', 'view-teacher-reports', 'view-class-wise-reports', 'view-family-wise-reports', 'export-reports', 'access-reports', 'generate-reports',
                    // Events Management (full)
                    'view-events', 'create-events', 'edit-events', 'delete-events', 'view-event-calendar', 'view-event-list', 'generate-events', 'view-event-management',
                    // Facility Management (full)
                    'view-facilities', 'create-facilities', 'edit-facilities', 'delete-facilities', 'manage-facility-maintenance', 'view-facility-management', 'access-facility-maintenance',
                    // Settings (limited)
                    'view-general-settings', 'view-notification-settings', 'access-settings',
                    // Messaging (full)
                    'access-messaging', 'send-messages', 'create-channels', 'manage-slack-integration', 'view-team-chat', 'access-slack',
                    // Profile
                    'view-profile', 'edit-profile', 'access-profile',
                    // Templates
                    'view-templates', 'access-template-gallery', 'use-templates'
                ]
            ]
        ];

        // Create roles and assign permissions
        foreach ($roles as $roleName => $roleData) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web'
            ]);

            // Update role with display fields if columns exist
            if (Schema::hasColumn('roles', 'display_name')) {
                $role->update([
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description']
                ]);
            }

            // Assign permissions
            if ($roleData['permissions'] === 'all') {
                $role->givePermissionTo(Permission::all());
            } else {
                $role->syncPermissions($roleData['permissions']);
            }
        }
    }

    private function getActionFromPermission($permission)
    {
        if (str_contains($permission, 'view')) return 'view';
        if (str_contains($permission, 'create')) return 'create';
        if (str_contains($permission, 'edit')) return 'edit';
        if (str_contains($permission, 'delete')) return 'delete';
        if (str_contains($permission, 'manage')) return 'manage';
        if (str_contains($permission, 'access')) return 'access';
        if (str_contains($permission, 'assign')) return 'assign';
        if (str_contains($permission, 'generate')) return 'generate';
        if (str_contains($permission, 'process')) return 'process';
        if (str_contains($permission, 'collect')) return 'collect';
        if (str_contains($permission, 'export')) return 'export';
        if (str_contains($permission, 'add')) return 'create';
        if (str_contains($permission, 'pay')) return 'pay';
        if (str_contains($permission, 'use')) return 'use';
        return 'other';
    }
}
