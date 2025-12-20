<?php

use Illuminate\Support\Facades\Storage;

/**
 * Format a date string to Pakistan standard (DD-MM-YYYY) with optional time.
 */
if (!function_exists('formatDatePk')) {
    function formatDatePk($date, $showTime = false)
    {
        if (empty($date)) {
            return '';
        }

        if (!$date instanceof DateTimeInterface) {
            try {
                $date = new DateTime($date);
            } catch (Exception $e) {
                return $date;
            }
        }

        $format = $showTime ? 'd-m-Y H:i' : 'd-m-Y';
        return $date->format($format);
    }
}

/**
 * Convert all input strings to uppercase and join with a space.
 */
if (!function_exists('capitalizeWords')) {
    function capitalizeWords(...$names)
    {
        $result = array_map(function($name) {
            return strtoupper(trim($name));
        }, $names);

        return count($result) === 1 ? $result[0] : implode(' ', $result);
    }
}

/**
 * Convert a snake_case string to Title Case.
 */
if (!function_exists('snakeToTitle')) {
    function snakeToTitle($value)
    {
        return ucwords(str_replace('_', ' ', strtolower(trim($value))));
    }
}

/**
 * Get user profile picture URL.
 */
if (!function_exists('get_user_profile_picture')) {
    function get_user_profile_picture($userId, $userType)
    {
        $path = "profile_pictures/{$userType}/{$userId}";

        if (Storage::exists($path)) {
            return Storage::url($path);
        }

        return null;
    }
}

/**
 * Generate initials from full name.
 */
if (!function_exists('get_user_initials')) {
    function get_user_initials($name)
    {
        $words = explode(' ', $name);
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        return $initials;
    }
}

/**
 * Get complete user profile data.
 */
if (!function_exists('get_user_profile_data')) {
    function get_user_profile_data($userId, $userType)
    {
        $model = "App\\Models\\" . ucfirst($userType);
        $user = $model::find($userId);

        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_picture' => get_user_profile_picture($userId, $userType),
            'initials' => get_user_initials($user->first_name . ' ' . $user->last_name)
        ];
    }
}

/**
 * Upload profile picture.
 */
if (!function_exists('upload_profile_picture')) {
    function upload_profile_picture($file, $userType, $userId)
    {
        try {
            $path = "profile_pictures/{$userType}/{$userId}";

            if (Storage::exists($path)) {
                Storage::delete($path);
            }

            $file->storeAs("profile_pictures/{$userType}", $userId);

            return $path;
        } catch (\Exception $e) {
            return false;
        }
    }
}

/**
 * Delete profile picture.
 */
if (!function_exists('delete_profile_picture')) {
    function delete_profile_picture($path)
    {
        try {
            if (Storage::exists($path)) {
                Storage::delete($path);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
