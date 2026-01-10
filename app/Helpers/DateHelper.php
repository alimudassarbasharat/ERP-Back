<?php

namespace App\Helpers;

use Carbon\Carbon;
use DateTimeInterface;

/**
 * Date Helper
 * 
 * Reusable date formatting and manipulation functions
 * Can be used across multiple projects
 */
class DateHelper
{
    /**
     * Format date to Pakistan standard (DD-MM-YYYY) with optional time
     * 
     * @param string|DateTimeInterface|null $date
     * @param bool $showTime
     * @return string
     */
    public static function formatDatePk($date, bool $showTime = false): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            $carbon = Carbon::parse($date);
            $format = $showTime ? 'd-m-Y H:i' : 'd-m-Y';
            return $carbon->format($format);
        } catch (\Exception $e) {
            return (string) $date;
        }
    }

    /**
     * Format date to ISO format (YYYY-MM-DD)
     * 
     * @param string|DateTimeInterface|null $date
     * @return string
     */
    public static function formatDateIso($date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return (string) $date;
        }
    }

    /**
     * Format date with time (YYYY-MM-DD HH:mm:ss)
     * 
     * @param string|DateTimeInterface|null $date
     * @return string
     */
    public static function formatDateTime($date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return (string) $date;
        }
    }

    /**
     * Format date to human readable (e.g., "2 days ago")
     * 
     * @param string|DateTimeInterface|null $date
     * @return string
     */
    public static function formatHumanReadable($date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            return Carbon::parse($date)->diffForHumans();
        } catch (\Exception $e) {
            return (string) $date;
        }
    }

    /**
     * Get date range for a period
     * 
     * @param string $period (today, week, month, year)
     * @return array ['start' => Carbon, 'end' => Carbon]
     */
    public static function getDateRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
        };
    }

    /**
     * Check if date is within range
     * 
     * @param string|DateTimeInterface $date
     * @param string|DateTimeInterface $start
     * @param string|DateTimeInterface $end
     * @return bool
     */
    public static function isWithinRange($date, $start, $end): bool
    {
        try {
            $dateCarbon = Carbon::parse($date);
            $startCarbon = Carbon::parse($start);
            $endCarbon = Carbon::parse($end);

            return $dateCarbon->between($startCarbon, $endCarbon);
        } catch (\Exception $e) {
            return false;
        }
    }
}
