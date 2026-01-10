<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * User Service
 * 
 * Handles User creation and management for Admins
 * Ensures every Admin has a corresponding User record for messaging system
 */
class UserService
{
    /**
     * Get or create User for Admin
     * 
     * @param Admin $admin
     * @return User
     */
    public function getOrCreateUserForAdmin(Admin $admin): User
    {
        if ($admin->user) {
            return $admin->user;
        }

        $existingUser = User::where('email', $admin->email)->first();
        if ($existingUser) {
            if (!$admin->user_id) {
                $admin->update(['user_id' => $existingUser->id]);
                $admin->refresh();
            }
            return $existingUser;
        }

        $user = User::create([
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => $admin->password,
            'status' => $admin->status ?? 'active',
            'avatar' => null,
        ]);

        $admin->update(['user_id' => $user->id]);
        $admin->refresh();

        return $user;
    }

    /**
     * Get User from authenticated user (handles Admin -> User conversion)
     * 
     * @param mixed $authUser
     * @return User|null
     */
    public function getUserFromAuth($authUser): ?User
    {
        if (!$authUser) {
            return null;
        }

        if ($authUser instanceof User) {
            return $authUser;
        }

        if ($authUser instanceof Admin) {
            return $this->getOrCreateUserForAdmin($authUser);
        }

        return null;
    }

    /**
     * Sync Admin data to User
     * 
     * @param Admin $admin
     * @param User $user
     * @return User
     */
    public function syncAdminToUser(Admin $admin, User $user): User
    {
        $user->update([
            'name' => $admin->name,
            'email' => $admin->email,
            'status' => $admin->status ?? 'active',
        ]);

        return $user->fresh();
    }
}
