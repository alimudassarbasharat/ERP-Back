<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        // Check if user has permission directly or through roles
        if ($user->can($permission)) {
            return $next($request);
        }

        // If user is an Admin model, check through the related User model
        if ($user instanceof \App\Models\Admin && $user->user) {
            if ($user->user->can($permission)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Insufficient permissions. Required: ' . $permission
        ], 403);
    }
}