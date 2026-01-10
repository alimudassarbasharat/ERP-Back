<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

trait TenantScope
{
    /**
     * Boot the tenant scope trait
     * Automatically applies merchant_id filtering to all queries
     */
    protected static function bootTenantScope()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $merchantId = self::getCurrentMerchantId();
            if ($merchantId) {
                $builder->where($builder->getModel()->getTable() . '.merchant_id', $merchantId);
            }
        });

        // Auto-set merchant_id when creating new records
        static::creating(function ($model) {
            if (empty($model->merchant_id)) {
                $merchantId = self::getCurrentMerchantId();
                if ($merchantId) {
                    $model->merchant_id = $merchantId;
                } else {
                    // In development, log a warning when merchant_id is missing
                    if (config('app.debug')) {
                        Log::warning('Creating model without merchant_id', [
                            'model' => get_class($model),
                            'attributes' => $model->getAttributes(),
                        ]);
                    }
                }
            }
        });

        // Validate merchant_id on update (prevent changing tenant ownership)
        static::updating(function ($model) {
            if ($model->isDirty('merchant_id')) {
                $original = $model->getOriginal('merchant_id');
                $new = $model->merchant_id;
                
                if ($original && $new && $original !== $new) {
                    Log::warning('Attempted to change merchant_id', [
                        'model' => get_class($model),
                        'id' => $model->id,
                        'original' => $original,
                        'new' => $new,
                    ]);
                    
                    // Prevent merchant_id change in production for security
                    if (!config('app.debug')) {
                        $model->merchant_id = $original;
                    }
                }
            }
        });
    }

    /**
     * Get current merchant_id from authenticated user
     */
    protected static function getCurrentMerchantId()
    {
        // Try to get from authenticated user (Admin or User)
        $user = auth()->user();
        
        if ($user) {
            // Admin has merchant_id directly
            if (isset($user->merchant_id) && $user->merchant_id) {
                return $user->merchant_id;
            }
            
            // User might be linked to Admin via relationship
            if (method_exists($user, 'admin') && $user->admin) {
                return $user->admin->merchant_id ?? null;
            }
            
            // Check if user has merchant_id directly
            if (property_exists($user, 'merchant_id') || isset($user->merchant_id)) {
                return $user->merchant_id ?? null;
            }
        }
        
        // Fallback: try to get from request attributes (set by TenantMiddleware)
        if (request() && request()->attributes->has('merchant_id')) {
            return request()->attributes->get('merchant_id');
        }
        
        // Fallback: try to get from request input (only in specific contexts)
        if (request() && request()->has('merchant_id')) {
            return request()->input('merchant_id');
        }
        
        return null;
    }

    /**
     * Scope to query without tenant filtering (use with caution)
     * Should only be used in admin/superadmin contexts
     */
    public function scopeWithoutTenantScope(Builder $query)
    {
        return $query->withoutGlobalScope('tenant');
    }

    /**
     * Scope to query for a specific merchant
     */
    public function scopeForMerchant(Builder $query, $merchantId)
    {
        return $query->withoutGlobalScope('tenant')
            ->where($this->getTable() . '.merchant_id', $merchantId);
    }

    /**
     * Check if current user can access this model instance
     */
    public function canAccess(): bool
    {
        $currentMerchantId = self::getCurrentMerchantId();
        
        if (!$currentMerchantId) {
            return false;
        }
        
        return $this->merchant_id === $currentMerchantId;
    }

    /**
     * Get merchant_id attribute (ensures it's always set)
     */
    public function getMerchantIdAttribute($value)
    {
        // If merchant_id is not set, try to get current one
        if (!$value && !$this->exists) {
            return self::getCurrentMerchantId();
        }
        
        return $value;
    }
}
