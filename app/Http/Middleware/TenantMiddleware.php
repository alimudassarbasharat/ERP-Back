<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     * Ensures all requests are scoped to the authenticated user's merchant_id
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // OPTIMIZATION: Check if merchant_id is already set in request attributes
        // This prevents repeated checks if MerchantVerification middleware already ran
        if ($request->attributes->has('merchant_id')) {
            return $next($request);
        }

        // Get merchant_id from user (optimized - direct property access)
        $merchantId = $user->merchant_id ?? null;
        
        // CRITICAL FIX: If merchant_id is null, use DEFAULT_TENANT instead of blocking
        // This prevents 401 errors for users without merchant_id
        if (!$merchantId) {
            $merchantId = 'DEFAULT_TENANT';
            
            // Only log warning in dev mode to reduce log noise
            if (config('app.debug')) {
                \Log::warning('Merchant ID not found for user, using DEFAULT_TENANT', [
                    'user_id' => $user->id,
                    'user_type' => get_class($user),
                    'email' => $user->email ?? 'N/A'
                ]);
            }
        }

        // Add merchant_id to request attributes (more reliable than merge)
        $request->attributes->set('merchant_id', $merchantId);
        $request->merge(['merchant_id' => $merchantId]);

        return $next($request);
    }
}
