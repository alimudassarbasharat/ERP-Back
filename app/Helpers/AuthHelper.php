<?php

namespace App\Helpers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Auth Helper
 * 
 * Reusable authentication helper functions
 * Can be used across multiple projects
 */
class AuthHelper
{
    /**
     * Get authenticated user (handles Admin -> User conversion)
     * 
     * @return User|Admin|null
     */
    public static function getAuthUser()
    {
        return Auth::user();
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        return Auth::check();
    }

    /**
     * Get authenticated user ID
     * 
     * @return int|null
     */
    public static function getAuthUserId(): ?int
    {
        $user = Auth::user();
        return $user ? $user->id : null;
    }

    /**
     * Check if authenticated user has role
     * 
     * @param string|array $roles
     * @return bool
     */
    public static function hasRole($roles): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($roles);
        }

        return false;
    }

    /**
     * Check if authenticated user has permission
     * 
     * @param string|array $permissions
     * @return bool
     */
    public static function hasPermission($permissions): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo($permissions);
        }

        return false;
    }

    /**
     * Get user type (admin, user, etc.)
     * 
     * @return string|null
     */
    public static function getUserType(): ?string
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }

        if ($user instanceof Admin) {
            return 'admin';
        }

        if ($user instanceof User) {
            return 'user';
        }

        return 'unknown';
    }
}
