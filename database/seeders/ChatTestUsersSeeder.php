<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ChatTestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates test users for chat/messaging testing
     * All users have password: "password"
     */
    public function run()
    {
        // Get or create a default merchant_id
        $merchantId = 'MERCH_CHAT_TEST';
        
        // Test users for chat testing
        $testUsers = [
            [
                'name' => 'Alice Teacher',
                'email' => 'alice@test.com',
                'password' => Hash::make('password'),
                'status' => 'active',
                'merchant_id' => $merchantId,
                'avatar' => 'https://ui-avatars.com/api/?name=Alice+Teacher&background=6366f1&color=fff',
            ],
            [
                'name' => 'Bob Teacher',
                'email' => 'bob@test.com',
                'password' => Hash::make('password'),
                'status' => 'active',
                'merchant_id' => $merchantId,
                'avatar' => 'https://ui-avatars.com/api/?name=Bob+Teacher&background=10b981&color=fff',
            ],
            [
                'name' => 'Charlie Student',
                'email' => 'charlie@test.com',
                'password' => Hash::make('password'),
                'status' => 'active',
                'merchant_id' => $merchantId,
                'avatar' => 'https://ui-avatars.com/api/?name=Charlie+Student&background=f59e0b&color=fff',
            ],
            [
                'name' => 'Diana Student',
                'email' => 'diana@test.com',
                'password' => Hash::make('password'),
                'status' => 'active',
                'merchant_id' => $merchantId,
                'avatar' => 'https://ui-avatars.com/api/?name=Diana+Student&background=ec4899&color=fff',
            ],
            [
                'name' => 'Eve Admin',
                'email' => 'eve@test.com',
                'password' => Hash::make('password'),
                'status' => 'active',
                'merchant_id' => $merchantId,
                'avatar' => 'https://ui-avatars.com/api/?name=Eve+Admin&background=8b5cf6&color=fff',
            ],
        ];

        foreach ($testUsers as $userData) {
            // Check if user already exists
            $existingUser = User::where('email', $userData['email'])->first();
            
            if (!$existingUser) {
                User::create($userData);
                $this->command->info("✅ Created user: {$userData['name']} ({$userData['email']})");
            } else {
                // Update password to "password" if user exists
                $existingUser->update([
                    'password' => Hash::make('password'),
                    'merchant_id' => $merchantId,
                    'status' => 'active'
                ]);
                $this->command->info("🔄 Updated user: {$userData['name']} ({$userData['email']})");
            }
        }

        $this->command->info("\n📋 Test Users Created/Updated:");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        foreach ($testUsers as $user) {
            $this->command->info("👤 {$user['name']}");
            $this->command->info("   📧 Email: {$user['email']}");
            $this->command->info("   🔑 Password: password");
            $this->command->info("");
        }
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ All test users are ready for chat testing!");
        $this->command->info("💬 You can now login with any of these users and test messaging.");
    }
}
