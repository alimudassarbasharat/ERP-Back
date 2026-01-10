<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Models\DirectMessageConversation;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CallController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function initiateCall(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:direct_message_conversations,id',
            'type' => 'required|in:audio,video'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::validationError($validator->errors());
        }

        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::notFound('User not found');
        }

        $conversation = DirectMessageConversation::findOrFail($request->conversation_id);

        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return ResponseHelper::forbidden('You are not a participant in this conversation');
        }

        broadcast(new \App\Events\CallInitiated($conversation, $user, $request->type))->toOthers();

        return ResponseHelper::success([
            'conversation_id' => $conversation->id,
            'type' => $request->type,
            'caller' => $user
        ], 'Call initiated');
    }

    public function answerCall(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:direct_message_conversations,id',
            'answer' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::validationError($validator->errors());
        }

        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::notFound('User not found');
        }

        $conversation = DirectMessageConversation::findOrFail($request->conversation_id);

        broadcast(new \App\Events\CallAnswered($conversation, $user, $request->answer))->toOthers();

        return ResponseHelper::success([
            'answered' => $request->answer
        ]);
    }

    public function endCall(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:direct_message_conversations,id'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::validationError($validator->errors());
        }

        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::notFound('User not found');
        }

        $conversation = DirectMessageConversation::findOrFail($request->conversation_id);

        broadcast(new \App\Events\CallEnded($conversation, $user))->toOthers();

        return ResponseHelper::success([], 'Call ended');
    }

    public function sendIceCandidate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:direct_message_conversations,id',
            'candidate' => 'required|array',
            'sdpMLineIndex' => 'nullable|integer',
            'sdpMid' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::validationError($validator->errors());
        }

        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::notFound('User not found');
        }

        $conversation = DirectMessageConversation::findOrFail($request->conversation_id);

        broadcast(new \App\Events\IceCandidateReceived($conversation, $user, $request->all()))->toOthers();

        return ResponseHelper::success([], 'ICE candidate sent');
    }

    public function sendOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:direct_message_conversations,id',
            'offer' => 'required|array'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::validationError($validator->errors());
        }

        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::notFound('User not found');
        }

        $conversation = DirectMessageConversation::findOrFail($request->conversation_id);

        broadcast(new \App\Events\WebRTCOffer($conversation, $user, $request->offer))->toOthers();

        return ResponseHelper::success([], 'Offer sent');
    }

    public function sendAnswer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:direct_message_conversations,id',
            'answer' => 'required|array'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::validationError($validator->errors());
        }

        $authUser = Auth::user();
        $user = $this->userService->getUserFromAuth($authUser);
        
        if (!$user) {
            return ResponseHelper::notFound('User not found');
        }

        $conversation = DirectMessageConversation::findOrFail($request->conversation_id);

        broadcast(new \App\Events\WebRTCAnswer($conversation, $user, $request->answer))->toOthers();

        return ResponseHelper::success([], 'Answer sent');
    }
}
