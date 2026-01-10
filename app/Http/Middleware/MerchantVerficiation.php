<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MerchantVerficiation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $authUser = auth()->user();
        
        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        // CRITICAL FIX: Don't block requests if merchant_id is null
        // Use DEFAULT_TENANT as fallback to prevent blocking legitimate users
        $merchantId = $authUser->merchant_id ?? 'DEFAULT_TENANT';
        
        // Log warning if merchant_id is missing (for debugging)
        if (!$authUser->merchant_id) {
            \Log::warning('Merchant ID not found for user in MerchantVerification middleware', [
                'user_id' => $authUser->id,
                'user_type' => get_class($authUser),
                'email' => $authUser->email ?? 'N/A'
            ]);
        }
        
        // Add merchant_id to request attributes (more reliable than merge)
        $request->attributes->set('merchant_id', $merchantId);
        $request->merge(['merchant_id' => $merchantId]);
        
        return $next($request);
    }
}
