<?php

namespace App\Helpers;

/**
 * String Helper
 * 
 * Reusable string manipulation and formatting functions
 * Can be used across multiple projects
 */
class StringHelper
{
    /**
     * Convert snake_case to Title Case
     * 
     * @param string $value
     * @return string
     */
    public static function snakeToTitle(string $value): string
    {
        return ucwords(str_replace('_', ' ', strtolower(trim($value))));
    }

    /**
     * Convert camelCase to Title Case
     * 
     * @param string $value
     * @return string
     */
    public static function camelToTitle(string $value): string
    {
        return ucwords(preg_replace('/([a-z])([A-Z])/', '$1 $2', $value));
    }

    /**
     * Capitalize all words
     * 
     * @param string ...$words
     * @return string
     */
    public static function capitalizeWords(string ...$words): string
    {
        $result = array_map(function ($word) {
            return strtoupper(trim($word));
        }, $words);

        return count($result) === 1 ? $result[0] : implode(' ', $result);
    }

    /**
     * Generate initials from full name
     * 
     * @param string $name
     * @param int $maxLength
     * @return string
     */
    public static function getInitials(string $name, int $maxLength = 2): string
    {
        $words = explode(' ', trim($name));
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word) && strlen($initials) < $maxLength) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        return $initials ?: strtoupper(substr($name, 0, $maxLength));
    }

    /**
     * Truncate string with ellipsis
     * 
     * @param string $string
     * @param int $length
     * @param string $suffix
     * @return string
     */
    public static function truncate(string $string, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($string) <= $length) {
            return $string;
        }

        return mb_substr($string, 0, $length) . $suffix;
    }

    /**
     * Sanitize string (remove HTML, trim, etc.)
     * 
     * @param string $string
     * @param bool $stripHtml
     * @return string
     */
    public static function sanitize(string $string, bool $stripHtml = true): string
    {
        $string = trim($string);

        if ($stripHtml) {
            $string = strip_tags($string);
        }

        return $string;
    }

    /**
     * Extract mentions from text (@username or @"Full Name")
     * 
     * @param string $text
     * @return array
     */
    public static function extractMentions(string $text): array
    {
        $mentions = [];
        preg_match_all('/@(\w+|"[^"]+")/', $text, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $mentions[] = trim($match, '"');
            }
        }

        return array_unique($mentions);
    }

    /**
     * Generate slug from string
     * 
     * @param string $string
     * @param string $separator
     * @return string
     */
    public static function slugify(string $string, string $separator = '-'): string
    {
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9-]/', $separator, $string);
        $string = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $string);
        
        return trim($string, $separator);
    }
}
