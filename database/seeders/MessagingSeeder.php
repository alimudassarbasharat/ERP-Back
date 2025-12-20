<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use App\Models\MessageReaction;
use App\Models\DirectMessageConversation;
use App\Models\DirectMessage;
use Faker\Factory as Faker;

class MessagingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        
        // Get all users using DB facade to avoid soft delete issues
        $users = \DB::table('users')->get()->map(function($user) {
            return (object) $user;
        });
        
        if ($users->count() < 2) {
            $this->command->error('Need at least 2 users. Creating test users...');
            
            // Create test users
            for ($i = 1; $i <= 10; $i++) {
                User::create([
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail,
                    'username' => $faker->unique()->userName,
                    'password' => bcrypt('password'),
                    'role' => $faker->randomElement(['student', 'teacher', 'admin']),
                    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($faker->name)
                ]);
            }
            
            $users = User::all();
        }
        
        $admin = $users->first();
        
        // Create default channels
        $channels = [
            [
                'name' => 'general',
                'description' => 'General discussion for all team members',
                'type' => 'public',
                'slug' => 'general'
            ],
            [
                'name' => 'announcements',
                'description' => 'Important company announcements',
                'type' => 'public',
                'slug' => 'announcements'
            ],
            [
                'name' => 'random',
                'description' => 'Random discussions and fun',
                'type' => 'public',
                'slug' => 'random'
            ],
            [
                'name' => 'dev-team',
                'description' => 'Development team discussions',
                'type' => 'private',
                'slug' => 'dev-team'
            ],
            [
                'name' => 'project-alpha',
                'description' => 'Discussion about Project Alpha',
                'type' => 'public',
                'slug' => 'project-alpha'
            ],
            [
                'name' => 'design',
                'description' => 'Design team collaboration',
                'type' => 'public',
                'slug' => 'design'
            ],
            [
                'name' => 'marketing',
                'description' => 'Marketing strategies and campaigns',
                'type' => 'public',
                'slug' => 'marketing'
            ],
            [
                'name' => 'support',
                'description' => 'Customer support discussions',
                'type' => 'public',
                'slug' => 'support'
            ]
        ];
        
        foreach ($channels as $channelData) {
            $channel = Channel::firstOrCreate(
                ['name' => $channelData['name']],
                array_merge($channelData, ['created_by' => $admin->id])
            );
            
            // Add random members to channel
            $memberCount = $channelData['type'] === 'private' ? rand(3, 5) : rand(5, $users->count());
            $members = $users->random(min($memberCount, $users->count()));
            
            foreach ($members as $member) {
                if (!$channel->isUserMember($member)) {
                    $role = $member->id === $admin->id ? 'admin' : 'member';
                    $channel->addMember($member, $role);
                }
            }
            
            // Add realistic messages with conversations
            if ($channel->messages()->count() === 0) {
                $this->seedChannelMessages($channel, $members);
            }
        }
        
        // Create direct message conversations
        $this->seedDirectMessages($users);
        
        $this->command->info('Messaging system seeded successfully with rich data!');
    }
    
    private function seedChannelMessages($channel, $members)
    {
        $faker = Faker::create();
        $messageCount = rand(20, 50);
        $messages = [];
        
        // Generate realistic conversations
        $topics = $this->getChannelTopics($channel->slug);
        
        for ($i = 0; $i < $messageCount; $i++) {
            $author = $members->random();
            $topic = $faker->randomElement($topics);
            
            $message = Message::create([
                'channel_id' => $channel->id,
                'user_id' => $author->id,
                'content' => $this->generateMessage($topic, $author->name),
                'type' => 'text',
                'created_at' => $faker->dateTimeBetween('-7 days', 'now')
            ]);
            
            $messages[] = $message;
            
            // Add reactions randomly
            if ($faker->boolean(30)) {
                $reactors = $members->random(rand(1, min(5, $members->count())));
                foreach ($reactors as $reactor) {
                    MessageReaction::firstOrCreate([
                        'message_id' => $message->id,
                        'user_id' => $reactor->id,
                        'reaction' => $faker->randomElement(['👍', '❤️', '😂', '🎉', '🚀', '👏', '💯', '🔥'])
                    ]);
                }
            }
            
            // Add thread replies occasionally
            if ($faker->boolean(20) && count($messages) > 5) {
                $parentMessage = $faker->randomElement($messages);
                $replyCount = rand(1, 5);
                
                for ($j = 0; $j < $replyCount; $j++) {
                    $replier = $members->random();
                    Message::create([
                        'channel_id' => $channel->id,
                        'user_id' => $replier->id,
                        'parent_id' => $parentMessage->id,
                        'content' => $this->generateReply($replier->name),
                        'type' => 'text',
                        'created_at' => $faker->dateTimeBetween($parentMessage->created_at, 'now')
                    ]);
                }
            }
        }
    }
    
    private function seedDirectMessages($users)
    {
        $faker = Faker::create();
        
        // Create some DM conversations
        $conversationCount = min(10, $users->count() * 2);
        
        for ($i = 0; $i < $conversationCount; $i++) {
            $participants = $users->random(rand(2, 4));
            
            // Create conversation
            $conversation = DirectMessageConversation::create([
                'type' => $participants->count() > 2 ? 'group' : 'direct',
                'name' => $participants->count() > 2 ? $faker->catchPhrase : null
            ]);
            
            // Add participants
            foreach ($participants as $participant) {
                $conversation->participants()->attach($participant->id, [
                    'last_read_at' => now(),
                    'unread_count' => 0
                ]);
            }
            
            // Add messages
            $messageCount = rand(5, 30);
            for ($j = 0; $j < $messageCount; $j++) {
                $sender = $participants->random();
                
                $message = DirectMessage::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $sender->id,
                    'content' => $faker->realText(rand(20, 200)),
                    'type' => 'text',
                    'created_at' => $faker->dateTimeBetween('-3 days', 'now')
                ]);
                
                // Add reactions occasionally
                if ($faker->boolean(20)) {
                    $reactor = $participants->random();
                    $message->reactions()->create([
                        'user_id' => $reactor->id,
                        'reaction' => $faker->randomElement(['👍', '❤️', '😊', '😂'])
                    ]);
                }
            }
        }
    }
    
    private function getChannelTopics($slug)
    {
        $topics = [
            'general' => [
                'team updates', 'office events', 'general questions',
                'company news', 'team building', 'workplace tips'
            ],
            'announcements' => [
                'policy updates', 'new features', 'maintenance windows',
                'holiday schedules', 'important deadlines', 'company milestones'
            ],
            'random' => [
                'funny stories', 'weekend plans', 'food recommendations',
                'movie discussions', 'hobbies', 'pets', 'memes'
            ],
            'dev-team' => [
                'code reviews', 'bug fixes', 'new technologies',
                'deployment issues', 'architecture decisions', 'best practices'
            ],
            'project-alpha' => [
                'project timeline', 'requirements', 'testing',
                'client feedback', 'sprint planning', 'progress updates'
            ],
            'design' => [
                'design trends', 'color schemes', 'user feedback',
                'mockup reviews', 'tool recommendations', 'inspiration'
            ],
            'marketing' => [
                'campaign ideas', 'social media', 'content strategy',
                'analytics', 'competitor analysis', 'branding'
            ],
            'support' => [
                'customer issues', 'bug reports', 'feature requests',
                'knowledge base', 'response templates', 'escalations'
            ]
        ];
        
        return $topics[$slug] ?? ['general discussion', 'team chat', 'updates'];
    }
    
    private function generateMessage($topic, $authorName)
    {
        $faker = Faker::create();
        
        $templates = [
            "Hey team, quick update on {topic}: {detail}",
            "Just wanted to share some thoughts about {topic}. {detail}",
            "Anyone have experience with {topic}? {detail}",
            "FYI - {detail} regarding {topic}",
            "@channel Important: {detail} for {topic}",
            "Good news everyone! {detail} about {topic}",
            "Question about {topic}: {detail}",
            "{detail} - thoughts on {topic}?",
            "Working on {topic} today. {detail}",
            "Update: {detail} for {topic} ✅"
        ];
        
        $details = [
            "Everything is on track and looking good",
            "We might need to adjust our approach",
            "I've found a solution that works well",
            "This needs immediate attention",
            "Great progress so far",
            "Let's discuss this in our next meeting",
            "I'll send more details shortly",
            "Please review and provide feedback",
            "This is now complete",
            "We're making excellent progress"
        ];
        
        $template = $faker->randomElement($templates);
        $detail = $faker->randomElement($details);
        
        $message = str_replace(
            ['{topic}', '{detail}'],
            [$topic, $detail],
            $template
        );
        
        // Add mentions occasionally
        if ($faker->boolean(30)) {
            $message = "@{$authorName} " . $message;
        }
        
        // Add emojis occasionally
        if ($faker->boolean(40)) {
            $emojis = ['👍', '🚀', '💪', '🎯', '✨', '🔥', '💡', '📈', '✅', '🎉'];
            $message .= ' ' . $faker->randomElement($emojis);
        }
        
        return $message;
    }
    
    private function generateReply($replierName)
    {
        $faker = Faker::create();
        
        $replies = [
            "Great point! I agree with this approach.",
            "Thanks for sharing! This is really helpful.",
            "I have a different perspective on this...",
            "Good idea! Let me add to that.",
            "+1 to this suggestion",
            "This makes sense. When can we start?",
            "I'll look into this and get back to you.",
            "Excellent work on this! 🎉",
            "Can you elaborate on this point?",
            "Following up on this thread..."
        ];
        
        return $faker->randomElement($replies);
    }
}