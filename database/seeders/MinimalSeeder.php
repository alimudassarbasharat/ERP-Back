<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MinimalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create basic user
        DB::statement("INSERT INTO users (name, email, password, created_at, updated_at) VALUES ('Test User', 'user@test.com', ?, ?, ?) ON CONFLICT (email) DO NOTHING", [
            Hash::make('password'),
            now(),
            now()
        ]);

        // Create admin user  
        DB::statement("INSERT INTO admins (name, email, password, role_id, status, merchant_id, created_at, updated_at) VALUES ('Admin User', 'admin@test.com', ?, 2, 'active', 'MERCH123', ?, ?) ON CONFLICT (email) DO NOTHING", [
            Hash::make('password'),
            now(),
            now()
        ]);

        echo "✅ Essential users created!\n";
        echo "📧 Admin: admin@test.com | Password: password\n";
        echo "👤 User: user@test.com | Password: password\n";
    }
}
