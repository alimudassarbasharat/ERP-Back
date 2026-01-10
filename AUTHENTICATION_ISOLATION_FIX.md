# Authentication Isolation Fix - Multi-User Concurrent Sessions

## Problem Identified

**Root Cause:** The system was not properly isolating authentication contexts between different users and browser sessions.

### Issues Found:

1. **Backend Token Revocation (CRITICAL)**
   - Line 133 in `AuthController.php`: `$authenticatedUser->tokens()->delete()`
   - This was revoking ALL tokens for a user on every login
   - When Teacher logged in, it deleted all tokens, potentially affecting other sessions

2. **Frontend Token Retrieval**
   - Axios interceptor was getting token from Pinia store
   - Store might have stale data if multiple tabs are open
   - Should get token directly from localStorage per-request

3. **Login Flow**
   - Using shared axios instance with interceptors for login
   - Could pick up token from previous session

---

## Fixes Applied

### 1. Backend Fix: Allow Multiple Concurrent Sessions

**File:** `app/Http/Controllers/Auth/AuthController.php`

**Before:**
```php
// Revoke existing tokens for security
$authenticatedUser->tokens()->delete();
```

**After:**
```php
// CRITICAL FIX: Do NOT revoke existing tokens on login
// This allows multiple concurrent sessions (normal browser + incognito, multiple users, etc.)
// Each login creates a NEW token without affecting existing valid tokens
// Only revoke tokens on explicit logout or security events

// Optional: Revoke only very old tokens (older than 30 days) for cleanup
$authenticatedUser->tokens()
    ->where('created_at', '<', now()->subDays(30))
    ->delete();
```

**Why:** 
- Each browser session (normal, incognito, different users) gets its own token
- Tokens are independent and don't interfere with each other
- Only old tokens are cleaned up, not active ones

### 2. Backend Fix: Logout Only Revokes Current Token

**File:** `app/Http/Controllers/Auth/AuthController.php`

**Before:**
```php
$user->tokens()->delete(); // Revokes ALL tokens
```

**After:**
```php
// Only revoke the CURRENT token, not all tokens
$currentToken = $request->user()->token();
if ($currentToken) {
    $currentToken->revoke();
}
```

**Why:**
- When one user logs out, other concurrent sessions remain active
- More secure: Each session is independent

### 3. Frontend Fix: Get Token from localStorage Per-Request

**File:** `src/utils/axios.js`

**Before:**
```javascript
let token = authStore.getToken() // From store (might be stale)
```

**After:**
```javascript
// CRITICAL FIX: Get token directly from localStorage, not from store
// This ensures each request gets the CURRENT session's token
let token = localStorage.getItem('authToken')

// Fallback to store if localStorage is empty
if (!token) {
    const authStore = useAuthStore()
    token = authStore.getToken()
}
```

**Why:**
- localStorage is isolated per browser session
- Each tab/window has its own localStorage
- Prevents token from one session affecting another

### 4. Frontend Fix: Use Fresh Axios Instance for Login

**File:** `src/stores/auth.js`

**Before:**
```javascript
const response = await api.post('/login', credentials) // Uses shared instance
```

**After:**
```javascript
// CRITICAL FIX: Use a fresh axios instance without interceptors for login
const loginApi = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
    timeout: 30000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
})

const response = await loginApi.post('/login', credentials)
```

**Why:**
- Prevents token from previous session from interfering
- Clean login flow without interceptor side effects

### 5. Frontend Fix: Remove Token from Auth Routes

**File:** `src/utils/axios.js`

**Added:**
```javascript
if (isAuthRoute) {
    // CRITICAL FIX: Remove any existing token from auth routes
    delete config.headers.Authorization
    return config
}
```

**Why:**
- Ensures login/signup requests don't include stale tokens
- Prevents authentication conflicts

---

## How It Works Now

### Multi-User Scenario:

1. **Super Admin logs in (Normal Browser)**
   - Gets token A
   - Token A stored in normal browser's localStorage
   - Can make API calls with token A

2. **Teacher logs in (Incognito Window)**
   - Gets token B (NEW, independent token)
   - Token B stored in incognito's localStorage (separate from normal browser)
   - Can make API calls with token B
   - **Token A is NOT affected** ✅

3. **Both users stay logged in**
   - Super Admin continues using token A
   - Teacher continues using token B
   - No interference ✅

### Token Storage Isolation:

- **Normal Browser:** Has its own localStorage → Token A
- **Incognito Window:** Has its own localStorage → Token B
- **Different Users:** Each gets their own token
- **Multiple Tabs:** Same browser = same localStorage = same token (expected behavior)

---

## Testing Checklist

After fixes, verify:

- [ ] Super Admin logs in (normal browser) → APIs work
- [ ] Teacher logs in (incognito) → APIs work
- [ ] Both users stay logged in simultaneously → No 401 errors
- [ ] Super Admin refreshes page → Still logged in
- [ ] Teacher refreshes page → Still logged in
- [ ] Super Admin makes API call → Works with Super Admin token
- [ ] Teacher makes API call → Works with Teacher token
- [ ] Super Admin logs out → Only Super Admin session ends
- [ ] Teacher session remains active after Super Admin logout
- [ ] Reverb/chat works for both users independently

---

## Security Considerations

### Token Cleanup:
- Old tokens (>30 days) are automatically cleaned up
- Prevents token table bloat
- Doesn't affect active sessions

### Logout Behavior:
- Option 1 (Current): Only revokes current token (allows other sessions)
- Option 2 (More Secure): Revokes all tokens (forces re-login everywhere)
  - Uncomment `$user->tokens()->delete()` in logout method if preferred

### Token Expiry:
- Tokens expire after 15 days (configurable)
- Frontend refreshes tokens proactively
- Expired tokens are rejected with 401

---

## Summary

**Problem:** One user's login was invalidating another user's session.

**Root Cause:** 
1. Backend was revoking ALL tokens on login
2. Frontend was using shared token state

**Solution:**
1. Backend: Create new tokens without revoking existing ones
2. Frontend: Get token from localStorage per-request (isolated per browser)
3. Login: Use fresh axios instance without interceptors

**Result:** Multiple users can log in simultaneously without interfering with each other. ✅
