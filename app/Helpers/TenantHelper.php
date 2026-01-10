<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TenantHelper
{
    /**
     * Get the current merchant_id from authenticated user
     * 
     * @return string|null
     */
    public static function currentMerchantId(): ?string
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }
        
        // Admin has merchant_id directly
        if (isset($user->merchant_id) && $user->merchant_id) {
            return $user->merchant_id;
        }
        
        // User might be linked to Admin via relationship
        if (method_exists($user, 'admin') && $user->admin) {
            return $user->admin->merchant_id ?? null;
        }
        
        // Fallback: try request attributes (set by TenantMiddleware)
        if (request() && request()->attributes->has('merchant_id')) {
            return request()->attributes->get('merchant_id');
        }
        
        return null;
    }

    /**
     * Validate that merchant_id is set
     * Throws exception if missing in development, logs warning in production
     * 
     * @param string|null $merchantId
     * @param string $context
     * @throws \Exception
     */
    public static function validateMerchantId(?string $merchantId, string $context = 'operation'): void
    {
        if (!$merchantId) {
            $message = "Attempted {$context} without merchant_id";
            
            Log::warning($message, [
                'user_id' => Auth::id(),
                'ip' => request()->ip(),
                'url' => request()->fullUrl(),
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
            ]);
            
            if (config('app.debug')) {
                throw new \Exception($message . '. This is a multi-tenant application and all operations must be scoped to a merchant.');
            }
        }
    }

    /**
     * Ensure data belongs to current merchant
     * 
     * @param mixed $model
     * @return bool
     */
    public static function belongsToCurrentMerchant($model): bool
    {
        if (!$model) {
            return false;
        }
        
        $currentMerchantId = self::currentMerchantId();
        
        if (!$currentMerchantId) {
            return false;
        }
        
        // Check if model has merchant_id
        if (!property_exists($model, 'merchant_id') && !isset($model->merchant_id)) {
            Log::warning('Model does not have merchant_id attribute', [
                'model' => get_class($model),
                'id' => $model->id ?? null,
            ]);
            return false;
        }
        
        return $model->merchant_id === $currentMerchantId;
    }

    /**
     * Get merchant_id from authenticated user and validate
     * 
     * @param string $context
     * @return string
     * @throws \Exception if merchant_id is not available
     */
    public static function requireMerchantId(string $context = 'operation'): string
    {
        $merchantId = self::currentMerchantId();
        self::validateMerchantId($merchantId, $context);
        
        if (!$merchantId) {
            throw new \Exception("merchant_id is required for {$context}");
        }
        
        return $merchantId;
    }

    /**
     * Check if current user is super admin (can bypass tenant scoping)
     * 
     * @return bool
     */
    public static function isSuperAdmin(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Check if user has super-admin role
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('super-admin');
        }
        
        return false;
    }

    /**
     * Execute callback without tenant scope (super admin only)
     * 
     * @param callable $callback
     * @return mixed
     * @throws \Exception if not super admin
     */
    public static function withoutTenantScope(callable $callback)
    {
        if (!self::isSuperAdmin() && !config('app.debug')) {
            throw new \Exception('Unauthorized: Only super admins can access data across tenants');
        }
        
        return $callback();
    }
}
