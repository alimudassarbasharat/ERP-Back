<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountryCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('country_codes')->insert([
            ['country_name' => 'Pakistan', 'country_code' => '+92', 'country_iso' => 'PK', 'flag_url' => 'https://flagcdn.com/w40/pk.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'India', 'country_code' => '+91', 'country_iso' => 'IN', 'flag_url' => 'https://flagcdn.com/w40/in.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'United States', 'country_code' => '+1', 'country_iso' => 'US', 'flag_url' => 'https://flagcdn.com/w40/us.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'United Kingdom', 'country_code' => '+44', 'country_iso' => 'GB', 'flag_url' => 'https://flagcdn.com/w40/gb.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'Canada', 'country_code' => '+1', 'country_iso' => 'CA', 'flag_url' => 'https://flagcdn.com/w40/ca.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'Australia', 'country_code' => '+61', 'country_iso' => 'AU', 'flag_url' => 'https://flagcdn.com/w40/au.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'Germany', 'country_code' => '+49', 'country_iso' => 'DE', 'flag_url' => 'https://flagcdn.com/w40/de.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'Saudi Arabia', 'country_code' => '+966', 'country_iso' => 'SA', 'flag_url' => 'https://flagcdn.com/w40/sa.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'United Arab Emirates', 'country_code' => '+971', 'country_iso' => 'AE', 'flag_url' => 'https://flagcdn.com/w40/ae.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'Bangladesh', 'country_code' => '+880', 'country_iso' => 'BD', 'flag_url' => 'https://flagcdn.com/w40/bd.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'Nepal', 'country_code' => '+977', 'country_iso' => 'NP', 'flag_url' => 'https://flagcdn.com/w40/np.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'China', 'country_code' => '+86', 'country_iso' => 'CN', 'flag_url' => 'https://flagcdn.com/w40/cn.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'Afghanistan', 'country_code' => '+93', 'country_iso' => 'AF', 'flag_url' => 'https://flagcdn.com/w40/af.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'Indonesia', 'country_code' => '+62', 'country_iso' => 'ID', 'flag_url' => 'https://flagcdn.com/w40/id.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['country_name' => 'Philippines', 'country_code' => '+63', 'country_iso' => 'PH', 'flag_url' => 'https://flagcdn.com/w40/ph.png', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
} 