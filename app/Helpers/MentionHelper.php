<?php

namespace App\Helpers;

class MentionHelper
{
    public static function parseMentions(string $text): array
    {
        preg_match_all('/@(\w+)/', $text, $matches, PREG_OFFSET_CAPTURE);
        
        $mentions = [];
        foreach ($matches[0] as $index => $match) {
            $mentions[] = [
                'username' => $matches[1][$index][0],
                'index' => $match[1],
                'length' => strlen($match[0])
            ];
        }
        
        return $mentions;
    }

    public static function extractMentionedUsernames(string $text): array
    {
        // Match @username patterns (alphanumeric, underscore, hyphen, spaces for full names)
        // Supports: @username, @user_name, @user-name, @"Full Name"
        preg_match_all('/@([\w\s\-]+)/', $text, $matches);
        $usernames = array_unique($matches[1] ?? []);
        
        // Trim and filter empty values
        return array_filter(array_map('trim', $usernames));
    }

    public static function findUsersByMentions(string $text, $userQuery): array
    {
        $usernames = self::extractMentionedUsernames($text);
        $users = [];

        foreach ($usernames as $username) {
            $user = $userQuery->where('name', 'like', "%{$username}%")
                ->orWhere('username', 'like', "%{$username}%")
                ->first();
            
            if ($user) {
                $users[] = $user;
            }
        }

        return $users;
    }

    public static function highlightMentions(string $text, array $users = []): string
    {
        $usernames = self::extractMentionedUsernames($text);
        
        foreach ($usernames as $username) {
            $user = collect($users)->first(function ($u) use ($username) {
                return stripos($u->name ?? '', $username) !== false || 
                       stripos($u->username ?? '', $username) !== false;
            });

            if ($user) {
                $text = str_ireplace(
                    "@{$username}",
                    "<span class='mention' data-user-id='{$user->id}'>@{$username}</span>",
                    $text
                );
            }
        }

        return $text;
    }
}
