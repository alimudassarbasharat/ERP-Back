<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessagingUserController extends Controller
{
    /**
     * Get current authenticated user for messaging
     */
    public function me()
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Get all users for messaging
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        
        $users = User::where('id', '!=', $currentUser->id)
            ->select('id', 'name', 'email', 'avatar', 'status')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get user by ID for messaging
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update user status for messaging
     */
    public function updateStatus(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'status' => 'required|in:online,away,offline'
        ]);

        $user->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $user
        ]);
    }
} 