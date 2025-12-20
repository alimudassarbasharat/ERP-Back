<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Allow both common development ports
        $allowedOrigins = ['http://localhost:5173', 'http://localhost:5174', 'http://localhost:8080', 'http://127.0.0.1:5173', 'http://127.0.0.1:5174'];
        $origin = $request->headers->get('Origin');
        
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } else {
            // Default to current port
            $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:5174');
        }
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
} 