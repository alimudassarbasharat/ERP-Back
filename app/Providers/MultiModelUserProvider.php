<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\Admin;
use App\Models\User;
use Laravel\Passport\Token;

/**
 * Multi-Model User Provider
 * 
 * Resolves users from both Admin and User models based on token's tokenable_type
 * This fixes the issue where Admin tokens work but User (Teacher) tokens return 401
 */
class MultiModelUserProvider implements UserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     * 
     * CRITICAL: This is called by Passport's TokenGuard
     * The identifier is the user_id from the JWT token
     * We need to check the token's tokenable_type to know which model to use
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        // CRITICAL FIX: Get the current request to access the bearer token
        // Then check the token's tokenable_type to know which model to use
        $request = request();
        $token = $request->bearerToken();
        
        if ($token) {
            // Find the token in database to get tokenable_type
            // Passport stores token ID as SHA256 hash of the token string
            $tokenId = hash('sha256', $token);
            $accessToken = Token::where('id', $tokenId)
                ->where('revoked', false)
                ->first();
            
            if ($accessToken) {
                // Resolve based on tokenable_type (this is the correct way)
                // Handle both full class name and short class name
                $tokenableType = $accessToken->tokenable_type;
                if ($tokenableType === Admin::class || $tokenableType === 'App\\Models\\Admin' || str_ends_with($tokenableType, 'Admin')) {
                    return Admin::find($accessToken->tokenable_id);
                } elseif ($tokenableType === User::class || $tokenableType === 'App\\Models\\User' || str_ends_with($tokenableType, 'User')) {
                    return User::find($accessToken->tokenable_id);
                }
            }
        }
        
        // Fallback: Try both models (for cases where token is not available)
        // Try Admin first (default provider)
        $user = Admin::find($identifier);
        if ($user) {
            return $user;
        }
        
        // Try User model
        $user = User::find($identifier);
        if ($user) {
            return $user;
        }
        
        return null;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        // Try Admin first
        $user = Admin::where('id', $identifier)->first();
        if ($user && $user->getRememberToken() === $token) {
            return $user;
        }
        
        // Try User model
        $user = User::where('id', $identifier)->first();
        if ($user && $user->getRememberToken() === $token) {
            return $user;
        }
        
        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->save();
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        // Try Admin first
        $user = Admin::where('email', $credentials['email'] ?? null)->first();
        if ($user) {
            return $user;
        }
        
        // Try User model
        $user = User::where('email', $credentials['email'] ?? null)->first();
        if ($user) {
            return $user;
        }
        
        return null;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return \Hash::check($credentials['password'] ?? '', $user->getAuthPassword());
    }
}
