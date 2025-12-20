<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SlackController extends Controller
{
    /**
     * Send message to Slack webhook
     */
    public function sendToSlack(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'channel' => 'nullable|string',
            'username' => 'nullable|string',
            'icon_emoji' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $webhookUrl = env('SLACK_WEBHOOK_URL');
        
        if (!$webhookUrl) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook URL not configured. Please add SLACK_WEBHOOK_URL to your .env file.'
            ], 400);
        }

        $payload = [
            'text' => $request->message,
            'channel' => $request->channel ?? '#general',
            'username' => $request->username ?? 'ERP Bot',
            'icon_emoji' => $request->icon_emoji ?? ':robot_face:'
        ];

        try {
            $response = Http::timeout(30)->post($webhookUrl, $payload);
            
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Message sent successfully'
                ]);
            } else {
                Log::error('Slack webhook failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send message'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Slack webhook error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sending message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Slack channels
     */
    public function getSlackChannels()
    {
        $token = env('SLACK_BOT_TOKEN');
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Bot token not configured. Please add SLACK_BOT_TOKEN to your .env file.',
                'data' => []
            ], 200);
        }

        try {
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->get('https://slack.com/api/conversations.list', [
                'types' => 'public_channel,private_channel',
                'limit' => 100
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['ok'] ?? false) {
                    return response()->json([
                        'success' => true,
                        'data' => $data['channels'] ?? []
                    ]);
                } else {
                    Log::error('Slack API error', ['response' => $data]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Slack API error: ' . ($data['error'] ?? 'Unknown error'),
                        'data' => []
                    ], 200);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to Slack API',
                    'data' => []
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error('Slack API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error connecting to Slack API: ' . $e->getMessage(),
                'data' => []
            ], 200);
        }
    }

    /**
     * Send message to specific Slack channel
     */
    public function sendToSlackChannel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'channel_id' => 'required|string',
            'message' => 'required|string',
            'attachments' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $token = env('SLACK_BOT_TOKEN');
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Slack bot token not configured'
            ], 400);
        }

        $payload = [
            'channel' => $request->channel_id,
            'text' => $request->message
        ];

        if (!empty($request->attachments)) {
            $payload['attachments'] = $request->attachments;
        }

        try {
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->post('https://slack.com/api/chat.postMessage', $payload);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['ok'] ?? false) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Message sent to Slack channel successfully',
                        'data' => $data
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Slack API error: ' . ($data['error'] ?? 'Unknown error')
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to Slack API'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Slack API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error connecting to Slack API: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Slack integration status
     */
    public function getIntegrationStatus()
    {
        $webhookUrl = env('SLACK_WEBHOOK_URL');
        $botToken = env('SLACK_BOT_TOKEN');
        
        $status = [
            'webhook_configured' => !empty($webhookUrl),
            'bot_token_configured' => !empty($botToken),
            'fully_configured' => !empty($webhookUrl) && !empty($botToken)
        ];

        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    /**
     * Test Slack connection
     */
    public function testConnection()
    {
        $results = [];
        
        // Test webhook
        $webhookUrl = env('SLACK_WEBHOOK_URL');
        if ($webhookUrl) {
            try {
                $response = Http::timeout(10)->post($webhookUrl, [
                    'text' => 'Test message from ERP System',
                    'username' => 'ERP Bot',
                    'icon_emoji' => ':test_tube:'
                ]);
                
                $results['webhook'] = [
                    'configured' => true,
                    'working' => $response->successful(),
                    'status' => $response->status()
                ];
            } catch (\Exception $e) {
                $results['webhook'] = [
                    'configured' => true,
                    'working' => false,
                    'error' => $e->getMessage()
                ];
            }
        } else {
            $results['webhook'] = [
                'configured' => false,
                'working' => false
            ];
        }

        // Test bot token
        $botToken = env('SLACK_BOT_TOKEN');
        if ($botToken) {
            try {
                $response = Http::timeout(10)->withHeaders([
                    'Authorization' => 'Bearer ' . $botToken
                ])->get('https://slack.com/api/auth.test');
                
                $data = $response->json();
                $results['bot_token'] = [
                    'configured' => true,
                    'working' => $response->successful() && ($data['ok'] ?? false),
                    'team' => $data['team'] ?? null,
                    'user' => $data['user'] ?? null
                ];
            } catch (\Exception $e) {
                $results['bot_token'] = [
                    'configured' => true,
                    'working' => false,
                    'error' => $e->getMessage()
                ];
            }
        } else {
            $results['bot_token'] = [
                'configured' => false,
                'working' => false
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
}