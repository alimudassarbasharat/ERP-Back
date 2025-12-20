<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class BasicMessagingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get first user as admin
        $admin = DB::table('users')->first();
        
        if (!$admin) {
            $this->command->error('No users found. Please create at least one user first.');
            return;
        }
        
        // Create default channels
        $channels = [
            [
                'name' => 'general',
                'description' => 'General discussion for all team members',
                'type' => 'public',
                'created_by' => $admin->id
            ],
            [
                'name' => 'announcements',
                'description' => 'Important company announcements',
                'type' => 'public',
                'created_by' => $admin->id
            ],
            [
                'name' => 'random',
                'description' => 'Random discussions and fun',
                'type' => 'public',
                'created_by' => $admin->id
            ],
            [
                'name' => 'dev-team',
                'description' => 'Development team discussions',
                'type' => 'private',
                'created_by' => $admin->id
            ]
        ];
        
        foreach ($channels as $channelData) {
            // Check if channel exists
            $existingChannel = DB::table('channels')->where('name', $channelData['name'])->first();
            
            if (!$existingChannel) {
                $channelId = DB::table('channels')->insertGetId(array_merge($channelData, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]));
                
                // Add admin to channel
                DB::table('channel_users')->insert([
                    'channel_id' => $channelId,
                    'user_id' => $admin->id,
                    'role' => 'admin',
                    'unread_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Add some initial messages
                $messages = $this->getInitialMessages($channelData['name']);
                foreach ($messages as $messageText) {
                    DB::table('messages')->insert([
                        'channel_id' => $channelId,
                        'user_id' => $admin->id,
                        'content' => $messageText,
                        'type' => 'text',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
        
        // Add all other users to general channel
        $generalChannel = DB::table('channels')->where('name', 'general')->first();
        if ($generalChannel) {
            $users = DB::table('users')->where('id', '!=', $admin->id)->get();
            foreach ($users as $user) {
                $exists = DB::table('channel_users')
                    ->where('channel_id', $generalChannel->id)
                    ->where('user_id', $user->id)
                    ->exists();
                    
                if (!$exists) {
                    DB::table('channel_users')->insert([
                        'channel_id' => $generalChannel->id,
                        'user_id' => $user->id,
                        'role' => 'member',
                        'unread_count' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
        
        $this->command->info('Basic messaging system seeded successfully!');
    }
    
    private function getInitialMessages($channelName)
    {
        $messages = [
            'general' => [
                'Welcome to the general channel! 👋',
                'This is where we discuss general topics.',
                'Feel free to share your thoughts and ideas here.'
            ],
            'announcements' => [
                'Welcome to the announcements channel! 📢',
                'Important company updates will be posted here.',
                'Please check this channel regularly.'
            ],
            'random' => [
                'Welcome to random! 🎉',
                'Share memes, jokes, and have fun here.',
                'Keep it friendly and respectful!'
            ],
            'dev-team' => [
                'Welcome to the dev team channel! 💻',
                'This is a private channel for development discussions.',
                'Share code, ask questions, and collaborate here.'
            ]
        ];
        
        return $messages[$channelName] ?? ['Welcome to this channel!'];
    }
}