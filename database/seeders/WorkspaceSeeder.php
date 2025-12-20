<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workspace;

class WorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchantId = 'SUPER123'; // Default merchant ID

        $workspaces = [
            [
                'merchant_id' => $merchantId,
                'name' => 'Technical Support',
                'slug' => 'technical-support',
                'icon' => 'Code',
                'color' => 'linear-gradient(135deg, #3b82f6, #2563eb)',
                'description' => 'Technical issues and bug reports',
                'is_active' => true
            ],
            [
                'merchant_id' => $merchantId,
                'name' => 'Finance Department',
                'slug' => 'finance-department',
                'icon' => 'DollarSign',
                'color' => 'linear-gradient(135deg, #10b981, #059669)',
                'description' => 'Finance and payment related tickets',
                'is_active' => true
            ],
            [
                'merchant_id' => $merchantId,
                'name' => 'Academic Affairs',
                'slug' => 'academic-affairs',
                'icon' => 'GraduationCap',
                'color' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)',
                'description' => 'Academic and curriculum related matters',
                'is_active' => true
            ],
            [
                'merchant_id' => $merchantId,
                'name' => 'HR Department',
                'slug' => 'hr-department',
                'icon' => 'Users',
                'color' => 'linear-gradient(135deg, #f59e0b, #d97706)',
                'description' => 'Human resources and staff management',
                'is_active' => true
            ],
            [
                'merchant_id' => $merchantId,
                'name' => 'Administration',
                'slug' => 'administration',
                'icon' => 'Settings',
                'color' => 'linear-gradient(135deg, #64748b, #475569)',
                'description' => 'General administration and operations',
                'is_active' => true
            ]
        ];

        foreach ($workspaces as $workspace) {
            Workspace::create($workspace);
        }
    }
}
