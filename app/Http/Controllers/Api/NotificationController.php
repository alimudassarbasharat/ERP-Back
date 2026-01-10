<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Models\MessageNotification;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get all notifications for authenticated user
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::unauthorized();
        }

        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $query = MessageNotification::where('user_id', $user->id)
            ->where('merchant_id', $merchantId)
            ->orderBy('created_at', 'desc');

        // Filter by unread only if requested
        // Handle both boolean and string values ('true', 'false', '1', '0')
        if ($request->has('unread_only')) {
            $unreadOnly = $request->unread_only;
            // Convert string to boolean if needed
            if (is_string($unreadOnly)) {
                $unreadOnly = filter_var($unreadOnly, FILTER_VALIDATE_BOOLEAN);
            }
            if ($unreadOnly) {
                $query->where('is_read', false);
            }
        }

        $perPage = $request->per_page ?? 50;
        $notifications = $query->paginate($perPage);

        return ResponseHelper::success($notifications);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::unauthorized();
        }

        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $notification = MessageNotification::where('id', $id)
            ->where('user_id', $user->id)
            ->where('merchant_id', $merchantId)
            ->first();

        if (!$notification) {
            return ResponseHelper::notFound('Notification not found');
        }

        $notification->markAsRead();

        return ResponseHelper::success($notification, 'Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::unauthorized();
        }

        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $updated = MessageNotification::where('user_id', $user->id)
            ->where('merchant_id', $merchantId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return ResponseHelper::success([
            'updated_count' => $updated
        ], 'All notifications marked as read');
    }

    /**
     * Get unread count
     */
    public function unreadCount()
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::unauthorized();
        }

        $merchantId = $user->merchant_id ?? request()->attributes->get('merchant_id') ?? 'DEFAULT_TENANT';
        
        $count = MessageNotification::where('user_id', $user->id)
            ->where('merchant_id', $merchantId)
            ->where('is_read', false)
            ->count();

        return ResponseHelper::success([
            'unread_count' => $count
        ]);
    }
}
