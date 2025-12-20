<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Http\Requests\Auth\SignUpRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UserRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
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

        $admin = Admin::where('email', $credentials['email'])->first();

        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }
        // foreach($admin as $adm){
        //     $adm->first_name = snakeToTitle($adm->first_name);
        //     $adm->last_name = snakeToTitle($adm->last_name);
        //     $adm->status = snakeToTitle($adm->status);
        // }

        // Revoke existing tokens
        $admin->tokens()->delete();
        
        // Create new token
        $token = $admin->createToken('auth_token')->accessToken;
        
        // Set cookie
        setcookie('auth_token', $token, time() + 43200, '/', 'localhost', false, true);
        
        return response()->json([
            'success' => true,
            'admin' => $admin,
            'access_token' => $token
        ]);
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
            \DB::table('password_resets')->updateOrInsert(
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
            $passwordReset = \DB::table('password_resets')
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
            \DB::table('password_resets')->where('email', $request->email)->delete();

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
        // Remove token from session
        session()->forget(['auth_token', 'admin']);
        
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function user(Request $request)
    {
        // Get admin from session
        $admin = session('admin');
        
        if (!$admin) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            'admin' => $admin
        ]);
    }
}
