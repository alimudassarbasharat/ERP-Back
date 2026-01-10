# Merchant ID Fix - Temporary Solution

## Issue
Users are getting "Merchant ID not found" error even though they are authenticated.

## Root Cause
1. Some users in database don't have `merchant_id` set
2. TenantMiddleware is blocking requests without merchant_id

## Temporary Fix Applied
TenantMiddleware now allows requests to proceed with a default merchant_id if user doesn't have one.

## Permanent Fix Required

### Step 1: Backfill merchant_id for existing users

Run this in tinker or create a migration:

```php
// For Admin users
\App\Models\Admin::whereNull('merchant_id')->each(function ($admin) {
    $admin->merchant_id = 'MERCH' . $admin->id; // Or use actual merchant_id
    $admin->save();
});

// For User (teachers, students, etc.)
\App\Models\User::whereNull('merchant_id')->each(function ($user) {
    // Get merchant_id from related admin or set default
    $admin = \App\Models\Admin::where('email', $user->email)->first();
    if ($admin && $admin->merchant_id) {
        $user->merchant_id = $admin->merchant_id;
    } else {
        $user->merchant_id = 'DEFAULT_TENANT'; // Or appropriate default
    }
    $user->save();
});
```

### Step 2: Ensure login response includes merchant_id

Login response already includes merchant_id (fixed in AuthController).

### Step 3: After backfilling, enable strict checking

In `TenantMiddleware.php`, uncomment the strict check:

```php
if (!$merchantId) {
    return response()->json([
        'success' => false,
        'message' => 'Merchant ID not found. Please contact administrator.'
    ], 403);
}
```

## Current Status
- ✅ TenantMiddleware allows requests with default merchant_id
- ✅ Login response includes merchant_id
- ⚠️ Need to backfill existing users' merchant_id
- ⚠️ Need to ensure all new users get merchant_id on creation
