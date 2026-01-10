<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Models\PushSubscription;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::unauthorized();
        }

        $validator = \Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'subscription.endpoint' => 'required|url',
            'subscription.keys.p256dh' => 'required|string',
            'subscription.keys.auth' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::validationError($validator->errors());
        }

        // Verify user_id matches authenticated user
        if ($user->id != $request->user_id) {
            return ResponseHelper::forbidden('Cannot subscribe for another user');
        }

        $merchantId = $user->merchant_id ?? 'DEFAULT_TENANT';

        // Create or update subscription
        $subscription = PushSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'endpoint' => $request->subscription['endpoint']
            ],
            [
                'public_key' => $request->subscription['keys']['p256dh'],
                'auth_token' => $request->subscription['keys']['auth'],
                'merchant_id' => $merchantId
            ]
        );

        return ResponseHelper::success($subscription, 'Successfully subscribed to push notifications');
    }

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe(Request $request)
    {
        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::unauthorized();
        }

        $merchantId = $user->merchant_id ?? 'DEFAULT_TENANT';

        // Delete all subscriptions for this user
        $deleted = PushSubscription::where('user_id', $user->id)
            ->where('merchant_id', $merchantId)
            ->delete();

        return ResponseHelper::success(['deleted_count' => $deleted], 'Successfully unsubscribed from push notifications');
    }
}
