<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use App\Http\Requests\Auth\SignUpRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UserRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\Client;

class AuthController extends Controller
{
    protected $clientRepository;
    protected $tokenRepository;

    public function __construct(ClientRepository $clientRepository, TokenRepository $tokenRepository)
    {
        $this->clientRepository = $clientRepository;
        $this->tokenRepository = $tokenRepository;
    }

    public function signUp(SignUpRequest $request){
        try{
            $validated = $request->validated();
            
            $admin = Admin::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone_number' => $validated['phone_number'] ?? null,
                'role_id' => $validated['role_id'] ?? 2, // Default to admin role
                'status' => 'active',
                'merchant_id' => 'MERCH' . time(),
            ]);

            // Get the personal access client
            $client = $this->clientRepository->personalAccessClient();
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'OAuth client not found. Please run passport:install command.'
                ], 500);
            }

            // Create token
            $tokenResult = $admin->createToken('MyApp');
            $token = $tokenResult->accessToken;

            return response()->json([
                'success' => true,
                'message' => 'Admin registered successfully',
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'phone_number' => $admin->phone_number,
                    'role' => $admin->role->name ?? '',
                    'status' => $admin->status,
                    'merchant_id' => $admin->merchant_id,
                    'token' => $token
                ]
            ], 201);

        } catch(\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } 
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        // Try to find user in Admin table first
        $admin = Admin::where('email', $credentials['email'])->first();
        $user = null;
        $authenticatedUser = null;
        $userType = null;

        if ($admin && Hash::check($credentials['password'], $admin->password)) {
            // Check if admin is active
            if ($admin->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not active. Please contact administrator.'
                ], 403);
            }
            $authenticatedUser = $admin;
            $userType = 'admin';
        } else {
            // Try to find user in User table (for teachers, test users, etc.)
            $user = User::where('email', $credentials['email'])->first();
            
            if ($user && Hash::check($credentials['password'], $user->password)) {
                // Check if user is active
                if ($user->status !== 'active') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account is not active. Please contact administrator.'
                    ], 403);
                }
                $authenticatedUser = $user;
                $userType = 'user';
            }
        }

        // If no user found or password doesn't match
        if (!$authenticatedUser) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // CRITICAL FIX: Do NOT revoke existing tokens on login
        // This allows multiple concurrent sessions (normal browser + incognito, multiple users, etc.)
        // Each login creates a NEW token without affecting existing valid tokens
        // Only revoke tokens on explicit logout or security events
        
        // Optional: Revoke only very old tokens (older than 30 days) for cleanup
        // This prevents token table bloat while allowing concurrent sessions
        $authenticatedUser->tokens()
            ->where('created_at', '<', now()->subDays(30))
            ->delete();
        
        // Create new token with Passport
        // Each token is unique and independent
        $tokenResult = $authenticatedUser->createToken('auth_token');
        $token = $tokenResult->accessToken;
        
        // Get token expiry (15 days from AuthServiceProvider)
        $expiresIn = now()->addDays(15)->diffInSeconds(now());
        
        // Prepare user data based on user type
        if ($userType === 'admin') {
            $userData = [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'phone_number' => $admin->phone_number,
                'role' => $admin->role->name ?? null,
                'status' => $admin->status,
                'merchant_id' => $admin->merchant_id,
            ];
        } else {
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => null,
                'role' => $user->roles->first()?->name ?? 'user',
                'status' => $user->status,
                'merchant_id' => $user->merchant_id ?? null, // CRITICAL: Include merchant_id in response
            ];
        }
        
        // Standardized response format (production-ready)
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'admin' => $userData // Keep 'admin' key for backward compatibility
        ], 200);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $admin = Admin::where('email', $request->email)->first();
            
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'We cannot find an admin with that email address.'
                ], 404);
            }
            
            // Generate a random token
            $token = Str::random(60);
            
            // Store the token in the password_resets table
            DB::table('password_resets')->updateOrInsert(
                ['email' => $admin->email],
                [
                    'token' => $token,
                    'created_at' => now()
                ]
            );
            
            // In a real application, you would send an email with the reset link
            // For now, we'll just return the token in the response
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent successfully',
                'token' => $token // In production, remove this line
            ], 200);

        } catch(\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process password reset request',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $passwordReset = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (!$passwordReset) {
                return response()->json([
                    'success' => false,
                    'message' => 'This password reset token is invalid.'
                ], 400);
            }

            $admin = Admin::where('email', $request->email)->first();
            
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'We cannot find an admin with that email address.'
                ], 404);
            }

            $admin->password = Hash::make($request->password);
            $admin->save();

            // Delete the token
            DB::table('password_resets')->where('email', $request->email)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ], 200);

        } catch(\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        if ($user) {
            // CRITICAL FIX: Only revoke the CURRENT token, not all tokens
            // This allows other concurrent sessions to remain active
            // Each browser/tab session has its own token, so only revoke the one being used
            
            $currentToken = $request->user()->token();
            if ($currentToken) {
                $currentToken->revoke();
            }
            
            // Alternative: If you want to revoke all tokens on logout (more secure but less convenient)
            // Uncomment the line below and comment out the above
            // $user->tokens()->delete();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ], 200);
    }

    public function user(Request $request)
    {
        // CRITICAL FIX: Get authenticated user from token
        // Passport stores tokenable_type and tokenable_id in oauth_access_tokens
        // We need to resolve the user based on the token, not just the guard's provider
        $authenticatedUser = $request->user();
        
        // CRITICAL FIX: If $request->user() returns null, resolve from token directly
        // This handles cases where Passport can't resolve the user due to multi-model setup
        if (!$authenticatedUser) {
            try {
                // Get the JWT token and decode it to get token ID
                $bearerToken = $request->bearerToken();
                if ($bearerToken) {
                    // Try to get token ID from request attributes (set by Passport middleware)
                    $tokenId = $request->attributes->get('oauth_access_token_id');
                    
                    if (!$tokenId) {
                        // Fallback: Hash the bearer token to get token ID
                        $tokenId = hash('sha256', $bearerToken);
                    }
                    
                    $accessToken = \Laravel\Passport\Token::where('id', $tokenId)
                        ->where('revoked', false)
                        ->first();
                    
                    if ($accessToken) {
                        // Resolve based on tokenable_type
                        $tokenableType = $accessToken->tokenable_type;
                        if ($tokenableType === Admin::class || $tokenableType === 'App\\Models\\Admin' || str_ends_with($tokenableType, 'Admin')) {
                            $authenticatedUser = Admin::find($accessToken->tokenable_id);
                        } elseif ($tokenableType === User::class || $tokenableType === 'App\\Models\\User' || str_ends_with($tokenableType, 'User')) {
                            $authenticatedUser = User::find($accessToken->tokenable_id);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error but don't break the request
                \Log::warning('Failed to resolve user from token', ['error' => $e->getMessage()]);
            }
        }
        
        if (!$authenticatedUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // CRITICAL FIX: Handle both Admin and User models
        $userData = [];
        $userType = '';

        if ($authenticatedUser instanceof Admin) {
            $userType = 'admin';
            $userData = [
                'id' => $authenticatedUser->id,
                'name' => $authenticatedUser->name,
                'email' => $authenticatedUser->email,
                'phone_number' => $authenticatedUser->phone_number,
                'role' => $authenticatedUser->role->name ?? null,
                'status' => $authenticatedUser->status,
                'merchant_id' => $authenticatedUser->merchant_id,
            ];
        } elseif ($authenticatedUser instanceof User) {
            $userType = 'user';
            $userData = [
                'id' => $authenticatedUser->id,
                'name' => $authenticatedUser->name,
                'email' => $authenticatedUser->email,
                'phone_number' => null,
                'role' => $authenticatedUser->roles->first()?->name ?? 'user',
                'status' => $authenticatedUser->status,
                'merchant_id' => $authenticatedUser->merchant_id,
            ];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unknown user type'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $userData,
            'type' => $userType
        ], 200);
    }

    /**
     * Refresh the access token
     * Note: Passport doesn't have built-in refresh tokens for personal access tokens
     * This endpoint creates a new token and revokes the old one
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Revoke current token
        $request->user()->token()->revoke();
        
        // Create new token
        $tokenResult = $user->createToken('auth_token');
        $token = $tokenResult->accessToken;
        $expiresIn = now()->addDays(15)->diffInSeconds(now());

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn
        ], 200);
    }
}
