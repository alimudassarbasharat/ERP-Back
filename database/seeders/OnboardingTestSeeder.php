<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OnboardingTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clean up existing test data
        User::whereIn('email', [
            'fresh@school.com',
            'incomplete@school.com', 
            'nosession@school.com',
            'complete@school.com'
        ])->forceDelete();
        
        School::whereIn('code', ['INC001', 'PAC001', 'EHS001'])->forceDelete();
        
        // Create test users for different onboarding states
        
        // 1. User with no school profile (fresh signup)
        $user1 = User::create([
            'name' => 'Fresh User',
            'email' => 'fresh@school.com',
            'password' => Hash::make('password123'),
            'merchant_id' => 1001,
            'email_verified_at' => now()
        ]);

        // 2. User with incomplete school profile
        $user2 = User::create([
            'name' => 'Incomplete Profile User',
            'email' => 'incomplete@school.com', 
            'password' => Hash::make('password123'),
            'merchant_id' => 1002,
            'email_verified_at' => now()
        ]);

        $incompleteSchool = School::create([
            'name' => 'Incomplete School',
            'code' => 'INC001',
            'phone_primary' => '+92300-1234567',
            'city' => 'Karachi',
            'country' => 'Pakistan',
            'timezone' => 'Asia/Karachi',
            'currency' => 'PKR',
            'date_format' => 'd/m/Y',
            'primary_color' => '#e91e63',
            'secondary_color' => '#f8f9fa',
            'week_start_day' => 'monday',
            'merchant_id' => 1002,
            'created_by' => $user2->id
        ]);

        // 3. User with complete profile but no session
        $user3 = User::create([
            'name' => 'No Session User',
            'email' => 'nosession@school.com',
            'password' => Hash::make('password123'),
            'merchant_id' => 1003,
            'email_verified_at' => now()
        ]);

        $noSessionSchool = School::create([
            'name' => 'Perfect Academy',
            'code' => 'PAC001',
            'school_type' => 'academy',
            'tagline' => 'Excellence in Education',
            'phone_primary' => '+92300-9876543',
            'phone_secondary' => '+92300-9876544',
            'email' => 'info@perfectacademy.edu.pk',
            'website' => 'https://perfectacademy.edu.pk',
            'country' => 'Pakistan',
            'state_province' => 'Sindh',
            'city' => 'Karachi',
            'address_line_1' => '123 Education Street',
            'address_line_2' => 'Block B, Gulshan-e-Iqbal',
            'postal_code' => '75300',
            'primary_color' => '#e91e63',
            'secondary_color' => '#f8f9fa',
            'timezone' => 'Asia/Karachi',
            'currency' => 'PKR',
            'date_format' => 'd/m/Y',
            'week_start_day' => 'monday',
            'default_email_sender_name' => 'Perfect Academy',
            'notification_channels_enabled' => ['email', 'sms'],
            'merchant_id' => 1003,
            'created_by' => $user3->id,
            'completed_at' => now()
        ]);

        // 4. User with complete onboarding (for testing completed state)
        $user4 = User::create([
            'name' => 'Complete User',
            'email' => 'complete@school.com',
            'password' => Hash::make('password123'),
            'merchant_id' => 1004,
            'email_verified_at' => now()
        ]);

        $completeSchool = School::create([
            'name' => 'Excellence High School',
            'code' => 'EHS001',
            'school_type' => 'school',
            'tagline' => 'Nurturing Future Leaders',
            'phone_primary' => '+92300-5555555',
            'phone_secondary' => '+92300-5555556',
            'email' => 'admin@excellencehigh.edu.pk',
            'website' => 'https://excellencehigh.edu.pk',
            'country' => 'Pakistan',
            'state_province' => 'Punjab',
            'city' => 'Lahore',
            'address_line_1' => '456 School Avenue',
            'address_line_2' => 'Model Town',
            'postal_code' => '54000',
            'primary_color' => '#2196f3',
            'secondary_color' => '#ffffff',
            'invoice_footer_text' => 'Thank you for choosing Excellence High School',
            'report_header_text' => 'Excellence High School - Academic Report',
            'timezone' => 'Asia/Karachi',
            'currency' => 'PKR',
            'date_format' => 'd-m-Y',
            'week_start_day' => 'monday',
            'default_email_sender_name' => 'Excellence High School',
            'default_whatsapp_sender' => '+923005555555',
            'notification_channels_enabled' => ['email', 'sms', 'whatsapp'],
            'merchant_id' => 1004,
            'created_by' => $user4->id,
            'completed_at' => now()
        ]);

        // Create active session for complete user
        Session::create([
            'name' => '2025-2026',
            'description' => 'Academic Session 2025-2026',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'active',
            'is_active' => true,
            'school_id' => $completeSchool->id,
            'notes' => 'Main academic session for the year 2025-2026',
            'merchant_id' => 1004,
            'created_by' => $user4->id
        ]);

        // Create a draft session for the no-session user to test session management
        Session::create([
            'name' => '2025-2026 Draft',
            'description' => 'Draft session for testing',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'draft',
            'is_active' => false,
            'school_id' => $noSessionSchool->id,
            'notes' => 'Draft session for testing activation',
            'merchant_id' => 1003,
            'created_by' => $user3->id
        ]);

        $this->command->info('Onboarding test data seeded successfully!');
        $this->command->info('Test Users Created:');
        $this->command->info('1. Fresh User (fresh@school.com) - No school profile');
        $this->command->info('2. Incomplete User (incomplete@school.com) - Incomplete profile');
        $this->command->info('3. No Session User (nosession@school.com) - Complete profile, no session');
        $this->command->info('4. Complete User (complete@school.com) - Fully onboarded');
        $this->command->info('Password for all users: password123');
    }
}