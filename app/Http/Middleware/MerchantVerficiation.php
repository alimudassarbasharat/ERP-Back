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
        if($authUser->merchant_id == null){
            return response()->json(['message' => 'Merchant not found'], 404);
        }
        $request['merchant_id'] = $authUser->merchant_id;
        return $next($request);
    }
}
