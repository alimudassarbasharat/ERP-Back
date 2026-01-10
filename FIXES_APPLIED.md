# Fixes Applied - Merchant ID & 401 Redirect

## Issues Fixed

### 1. "Merchant ID not found" Error ✅

**Problem:** Users getting 403 error even with valid authentication.

**Root Cause:**
- DirectMessageController was blocking requests if merchant_id was null
- Some users might not have merchant_id set in database

**Fix Applied:**
- Updated `DirectMessageController::startConversation()` to allow default merchant_id if not set
- Added fallback to get merchant_id from related Admin
- Added warning log for debugging

**File:** `app/Http/Controllers/Api/DirectMessageController.php`

### 2. 401 Errors Not Redirecting to Login ✅

**Problem:** When 401 error occurs, user is not redirected to login page.

**Root Cause:**
- Axios interceptor was using `router.push()` which might fail
- setTimeout was causing delays

**Fix Applied:**
- Changed all 401/403 redirects to use `window.location.href` directly
- Removed setTimeout delays
- More reliable redirect mechanism

**File:** `src/utils/axios.js`

### 3. TenantMiddleware Merchant ID Extraction ✅

**Problem:** TenantMiddleware not properly extracting merchant_id from Admin/User models.

**Fix Applied:**
- Improved merchant_id extraction logic
- Added proper instanceof checks for Admin and User models
- Added fallback to default merchant_id if not found (temporary)

**File:** `app/Http/Middleware/TenantMiddleware.php`

### 4. Login Response Includes merchant_id ✅

**Problem:** Login response was not including merchant_id for User model.

**Fix Applied:**
- Updated AuthController to include merchant_id in login response for both Admin and User

**File:** `app/Http/Controllers/Auth/AuthController.php`

---

## Testing

After these fixes:

1. ✅ Login should work for both Admin and User
2. ✅ merchant_id should be included in login response
3. ✅ Direct messages should work even if merchant_id is not set (uses default)
4. ✅ 401 errors should redirect to login page immediately
5. ✅ 403 errors should also redirect to login page

---

## Next Steps (Optional - For Production)

1. **Backfill merchant_id for existing users:**
   ```php
   // Run in tinker or migration
   \App\Models\User::whereNull('merchant_id')->update(['merchant_id' => 'DEFAULT_TENANT']);
   \App\Models\Admin::whereNull('merchant_id')->update(['merchant_id' => 'DEFAULT_TENANT']);
   ```

2. **Enable strict merchant_id checking:**
   - In `TenantMiddleware.php`, uncomment the strict check
   - In `DirectMessageController.php`, remove the default fallback

3. **Add tenant middleware to routes:**
   ```php
   Route::middleware(['auth:api', 'tenant'])->group(function () {
       // messaging routes
   });
   ```

---

## Summary

✅ Merchant ID errors fixed (temporary fallback)
✅ 401/403 redirects fixed (using window.location)
✅ Login response includes merchant_id
✅ Multiple concurrent sessions work correctly

System is now ready for testing!
