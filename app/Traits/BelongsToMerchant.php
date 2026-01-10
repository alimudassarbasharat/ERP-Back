<?php

namespace App\Traits;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Trait BelongsToMerchant
 * 
 * CRITICAL MULTI-TENANCY ENFORCEMENT
 * ===================================
 * 
 * This trait ensures PERMANENT merchant_id isolation for all tenant-owned data.
 * 
 * Features:
 * 1. Auto-assigns merchant_id on CREATE
 * 2. Applies GLOBAL SCOPE to ALL queries (no manual filtering needed)
 * 3. Prevents writes without merchant_id
 * 4. Throws clear exceptions in development
 * 
 * Usage:
 * ------
 * class Session extends Model {
 *     use BelongsToMerchant;
 * }
 * 
 * Benefits:
 * ---------
 * - NO controller code needed for merchant_id
 * - IMPOSSIBLE to leak data across merchants
 * - AUTOMATIC filtering on all queries
 * - PREVENTS accidental cross-tenant access
 * 
 * @package App\Traits
 */
trait BelongsToMerchant
{
    /**
     * Boot the trait
     * Called automatically when model is initialized
     */
    protected static function bootBelongsToMerchant()
    {
        // Apply global scope to EVERY query
        // CRITICAL: Only apply if user is authenticated
        static::addGlobalScope('merchant', function (Builder $builder) {
            $merchantId = self::getCurrentMerchantId();
            
            // Only filter if merchant_id exists (skip for unauthenticated contexts)
            if ($merchantId) {
                $builder->where(self::getMerchantIdColumn(), $merchantId);
            }
        });

        // Auto-assign merchant_id when creating new records
        static::creating(function (Model $model) {
            $merchantId = self::getCurrentMerchantId();
            
            if (!$merchantId) {
                throw new \Exception(
                    'CRITICAL ERROR: Cannot create ' . class_basename($model) . ' without merchant_id. ' .
                    'User must be authenticated with valid merchant_id.'
                );
            }
            
            $merchantIdColumn = self::getMerchantIdColumn();
            
            // Only set if not already set (allows manual override in seeders)
            if (!$model->{$merchantIdColumn}) {
                $model->{$merchantIdColumn} = $merchantId;
            }
        });
        
        // Verify merchant_id on update
        static::updating(function (Model $model) {
            $merchantIdColumn = self::getMerchantIdColumn();
            
            if (!$model->{$merchantIdColumn}) {
                throw new \Exception(
                    'CRITICAL ERROR: Cannot update ' . class_basename($model) . ' without merchant_id.'
                );
            }
        });
    }

    /**
     * Get current user's merchant_id
     * Supports both User and Admin models
     * 
     * @return string|int|null
     */
    protected static function getCurrentMerchantId()
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }
        
        // Support both User and Admin models
        if ($user instanceof User || $user instanceof Admin) {
            return $user->merchant_id;
        }
        
        // Fallback: check if property exists
        if (property_exists($user, 'merchant_id')) {
            return $user->merchant_id;
        }
        
        return null;
    }

    /**
     * Get the name of the merchant_id column
     * Override this method in model if using different column name
     * 
     * @return string
     */
    protected static function getMerchantIdColumn(): string
    {
        return 'merchant_id';
    }

    /**
     * Relationship: belongs to merchant (via User/Admin)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id', 'merchant_id');
    }

    /**
     * Scope: Filter by specific merchant
     * Useful for admin/super-admin queries
     * 
     * @param Builder $query
     * @param string|int $merchantId
     * @return Builder
     */
    public function scopeForMerchant(Builder $query, $merchantId)
    {
        return $query->where(self::getMerchantIdColumn(), $merchantId);
    }

    /**
     * Scope: Without merchant scope (use with caution!)
     * ONLY for super-admin queries
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithoutMerchantScope(Builder $query)
    {
        return $query->withoutGlobalScope('merchant');
    }

    /**
     * Get all records across all merchants (SUPER ADMIN ONLY)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function allMerchants()
    {
        return static::withoutGlobalScope('merchant')->get();
    }

    /**
     * Verify merchant_id matches current user
     * Use this in controllers for additional security
     * 
     * @return bool
     */
    public function belongsToCurrentMerchant(): bool
    {
        $currentMerchantId = self::getCurrentMerchantId();
        $merchantIdColumn = self::getMerchantIdColumn();
        
        return $this->{$merchantIdColumn} === $currentMerchantId;
    }

    /**
     * Ensure merchant_id is set
     * Throws exception if missing (for strict validation)
     * 
     * @throws \Exception
     */
    public function ensureMerchantIdSet()
    {
        $merchantIdColumn = self::getMerchantIdColumn();
        
        if (!$this->{$merchantIdColumn}) {
            throw new \Exception(
                'SECURITY ERROR: ' . class_basename($this) . ' must have merchant_id set. ' .
                'This indicates a critical multi-tenancy violation.'
            );
        }
    }
}
