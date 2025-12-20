<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $merchantId = 'MERCH' . time(); // Generate a unique merchant ID

        $admins = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password123'),
                'phone_number' => '03001234567',
                'role_id' => Role::where('name', 'super-admin')->first()->id,
                'status' => 'active',
                'merchant_id' => $merchantId
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'phone_number' => '03001234568',
                'role_id' => Role::where('name', 'admin')->first()->id,
                'status' => 'active',
                'merchant_id' => $merchantId
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@example.com',
                'password' => Hash::make('password123'),
                'phone_number' => '03001234569',
                'role_id' => Role::where('name', 'manager')->first()->id,
                'status' => 'active',
                'merchant_id' => $merchantId
            ]
        ];

        foreach ($admins as $admin) {
            Admin::create($admin);
        }
    }
}
