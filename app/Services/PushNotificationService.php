<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected $vapidPublicKey;
    protected $vapidPrivateKey;

    public function __construct()
    {
        $this->vapidPublicKey = config('services.push.vapid.public_key');
        $this->vapidPrivateKey = config('services.push.vapid.private_key');
    }

    /**
     * Send push notification to user
     */
    public function sendToUser(User $user, array $notificationData)
    {
        if (!$this->vapidPublicKey || !$this->vapidPrivateKey) {
            Log::warning('[Push] VAPID keys not configured, skipping push notification');
            return false;
        }

        $merchantId = $user->merchant_id ?? 'DEFAULT_TENANT';
        
        // Get all push subscriptions for this user
        $subscriptions = PushSubscription::where('user_id', $user->id)
            ->where('merchant_id', $merchantId)
            ->get();

        if ($subscriptions->isEmpty()) {
            return false;
        }

        $auth = [
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => $this->vapidPublicKey,
                'privateKey' => $this->vapidPrivateKey,
            ],
        ];

        // Check if WebPush class exists (package installed)
        if (!class_exists(\Minishlink\WebPush\WebPush::class)) {
            Log::warning('[Push] WebPush package not installed. Run: composer require minishlink/web-push');
            return false;
        }

        $webPush = new \Minishlink\WebPush\WebPush($auth);

        $successCount = 0;
        $failCount = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $pushSubscription = \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'keys' => [
                        'p256dh' => $subscription->public_key,
                        'auth' => $subscription->auth_token,
                    ],
                ]);

                $result = $webPush->sendOneNotification(
                    $pushSubscription,
                    json_encode($notificationData)
                );

                if ($result->isSuccess()) {
                    $successCount++;
                } else {
                    $failCount++;
                    // If subscription is invalid, remove it
                    if ($result->isSubscriptionExpired()) {
                        $subscription->delete();
                    }
                }
            } catch (\Exception $e) {
                Log::error('[Push] Failed to send push notification:', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                $failCount++;
            }
        }

        // Flush any pending notifications
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return $successCount > 0;
    }

    /**
     * Send DM notification via push
     */
    public function sendDMNotification(User $recipient, $senderName, $conversationName, $messagePreview, $conversationId)
    {
        $notificationData = [
            'title' => $senderName,
            'body' => $messagePreview ?: 'New message',
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => "dm-{$conversationId}",
            'requireInteraction' => false,
            'conversation_id' => $conversationId,
            'conversation_type' => 'dm',
            'url' => config('app.frontend_url', 'http://localhost:5173') . '/messaging?dm=' . $conversationId
        ];

        return $this->sendToUser($recipient, $notificationData);
    }

    /**
     * Send channel notification via push
     */
    public function sendChannelNotification(User $recipient, $senderName, $channelName, $messagePreview, $channelId)
    {
        $notificationData = [
            'title' => "{$senderName} in {$channelName}",
            'body' => $messagePreview ?: 'New message',
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => "channel-{$channelId}",
            'requireInteraction' => false,
            'conversation_id' => $channelId,
            'conversation_type' => 'channel',
            'url' => config('app.frontend_url', 'http://localhost:5173') . '/messaging?channel=' . $channelId
        ];

        return $this->sendToUser($recipient, $notificationData);
    }
}
