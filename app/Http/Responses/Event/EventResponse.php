<?php

namespace App\Http\Responses\Event;

use App\Models\Event;
use Illuminate\Http\JsonResponse;

class EventResponse
{
    public static function success($data, $message = 'Success', $status = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public static function error($message = 'Error', $status = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message
        ], $status);
    }

    public static function list($events): JsonResponse
    {
        return self::success($events, 'Events retrieved successfully');
    }

    public static function created(Event $event): JsonResponse
    {
        return self::success($event, 'Event created successfully', 201);
    }

    public static function updated(Event $event): JsonResponse
    {
        return self::success($event, 'Event updated successfully');
    }

    public static function deleted(): JsonResponse
    {
        return self::success(null, 'Event deleted successfully', 204);
    }

    public static function notFound(): JsonResponse
    {
        return self::error('Event not found', 404);
    }

    public static function unauthorized(): JsonResponse
    {
        return self::error('Unauthorized access', status: 403);
    }

    public static function validationError($errors): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }
} 