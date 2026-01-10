# Multi-Tenant ERP Architecture Documentation

## Overview

This ERP system is designed as a **multi-tenant SaaS application** where each school operates as an isolated tenant. Data from one school must NEVER be visible to another school.

## Core Principle: Merchant ID Isolation

Every piece of data in the system is tied to a `merchant_id`, which represents a school/organization. This ensures complete data isolation between tenants.

---

## 1. Database Structure

### Required Fields

**Every critical table MUST have:**
- `merchant_id` (string, required, indexed)
- `deleted_at` (timestamp, nullable) for soft deletes

### Tables with Merchant ID

**User & Authentication:**
- `users` - All users (teachers, students, faculty)
- `admins` - School administrators

**Messaging & Communication:**
- `channels` - Group channels
- `channel_users` - Channel membership (pivot)
- `messages` - Channel messages
- `direct_message_conversations` - DM conversations
- `direct_message_participants` - DM participants (pivot)
- `direct_messages` - Direct messages
- `message_reactions` - Message reactions
- `message_attachments` - Message attachments
- `mention_notifications` - @mention notifications
- `user_presence` - Online/offline status

**Academic Data:**
- `students` - Student records
- `teachers` - Teacher records
- `classes` - Class records
- `sections` - Section records
- `subjects` - Subject records
- `exams` - Exam records
- `attendance_records` - Attendance data

**Other:**
- `departments` - Department records
- All other business-critical tables

---

## 2. Tenant Scope Trait

### Location
`app/Traits/TenantScope.php`

### Purpose
Automatically filters all queries by `merchant_id` to ensure tenant isolation.

### Usage
```php
use App\Traits\TenantScope;

class Channel extends Model
{
    use TenantScope;
    
    protected $fillable = [
        'name',
        'merchant_id', // Required
        // ... other fields
    ];
}
```

### How It Works

1. **Global Scope**: Automatically adds `WHERE merchant_id = ?` to all queries
2. **Auto-Set on Create**: Automatically sets `merchant_id` when creating new records
3. **Bypass Methods**: 
   - `withoutTenantScope()` - Query without tenant filter (use with caution)
   - `forMerchant($merchantId)` - Query for specific merchant

### Example
```php
// This automatically filters by merchant_id
$channels = Channel::all(); // Only returns channels for current merchant

// To bypass (admin only)
$allChannels = Channel::withoutTenantScope()->get();

// To query specific merchant
$schoolChannels = Channel::forMerchant('SCHOOL123')->get();
```

---

## 3. Tenant Middleware

### Location
`app/Http/Middleware/TenantMiddleware.php`

### Purpose
Ensures every authenticated request has a valid `merchant_id` and adds it to the request.

### Usage
Add to routes that require tenant isolation:

```php
Route::middleware(['auth:api', 'tenant'])->group(function () {
    Route::get('/channels', [ChannelController::class, 'index']);
    // ... other routes
});
```

### How It Works

1. Gets authenticated user
2. Extracts `merchant_id` from user (Admin or User model)
3. Adds `merchant_id` to request attributes
4. Returns 403 if `merchant_id` is missing

---

## 4. User Roles & Structure

### Role Hierarchy

1. **Super Admin (School Owner)**
   - Has `merchant_id`
   - Full control over school
   - Manages all users, settings, data

2. **Faculty (Management)**
   - Belongs to same `merchant_id`
   - Can manage teachers/students under their scope
   - Limited admin access

3. **Teachers**
   - Belongs to same `merchant_id`
   - Can interact with students
   - Can use chat, channels
   - No admin access

4. **Students**
   - Belongs to same `merchant_id`
   - Can chat with teachers/faculty
   - Can access only their own academic data
   - Very limited permissions

### User Models

**Admin Model** (`app/Models/Admin.php`):
- Extends `Authenticatable`
- Has `merchant_id` directly
- Used for school administrators

**User Model** (`app/Models/User.php`):
- Extends `Authenticatable`
- Has `merchant_id`
- Used for teachers, students, faculty
- Can be linked to Admin via relationship

---

## 5. Authentication Flow

### Login Process

1. User logs in with email/password
2. System checks both `Admin` and `User` tables
3. If found, validates password
4. Creates Passport token
5. Returns token + user data (including `merchant_id`)

### Token Usage

- Frontend stores token in `localStorage`
- All API requests include: `Authorization: Bearer {token}`
- Backend extracts user from token
- Tenant middleware ensures `merchant_id` is available

---

## 6. Messaging System (Tenant-Safe)

### Direct Messages

**Creating DM:**
```php
$conversation = DirectMessageConversation::findOrCreateBetweenUsers(
    $user1Id,
    $user2Id,
    $merchantId // Required - ensures users are from same tenant
);
```

**Sending Message:**
```php
$message = DirectMessage::create([
    'conversation_id' => $conversation->id,
    'user_id' => $user->id,
    'content' => $content,
    'merchant_id' => $merchantId // Auto-set by TenantScope
]);
```

### Channels

**Creating Channel:**
```php
$channel = Channel::create([
    'name' => $name,
    'created_by' => $user->id,
    'merchant_id' => $merchantId // Auto-set by TenantScope
]);
```

**Adding Members:**
- Only users with same `merchant_id` can be added
- System validates this automatically

---

## 7. Broadcast Channels (Reverb)

### Channel Authorization

All broadcast channels must check `merchant_id`:

```php
// routes/channels.php
Broadcast::channel('dm.{conversationId}', function ($user, $conversationId) {
    $conversation = DirectMessageConversation::find($conversationId);
    
    // Ensure user belongs to same merchant
    if ($conversation->merchant_id !== $user->merchant_id) {
        return false; // Reject access
    }
    
    return $conversation->participants->contains($user->id);
});
```

### Event Broadcasting

All events must include `merchant_id` in payload:

```php
broadcast(new DirectMessageSent($message, $conversation))
    ->toOthers(); // Only sends to users in same merchant
```

---

## 8. Controller Best Practices

### Always Get Merchant ID

```php
public function index()
{
    $merchantId = request()->attributes->get('merchant_id');
    // or
    $merchantId = auth()->user()->merchant_id;
    
    // Use in queries
    $channels = Channel::forMerchant($merchantId)->get();
}
```

### Validate Tenant Access

```php
public function show($id)
{
    $channel = Channel::find($id);
    
    // Ensure user can access this channel (same merchant)
    if ($channel->merchant_id !== auth()->user()->merchant_id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
    return response()->json($channel);
}
```

---

## 9. Migration Strategy

### Making Merchant ID Required

Run migration:
```bash
php artisan migrate
```

This migration:
1. Makes `merchant_id` NOT NULL on all critical tables
2. Adds indexes for performance
3. Backfills existing data (if needed)

### Backfilling Data

Before making `merchant_id` required, ensure all existing records have a valid `merchant_id`:

```php
// Example: Backfill users
User::whereNull('merchant_id')->each(function ($user) {
    $user->merchant_id = 'DEFAULT_TENANT'; // Replace with actual logic
    $user->save();
});
```

---

## 10. Testing Checklist

### Tenant Isolation Tests

- [ ] User from School A cannot see channels from School B
- [ ] User from School A cannot send DMs to users from School B
- [ ] User from School A cannot access messages from School B
- [ ] Admin from School A cannot see students from School B
- [ ] All API endpoints return only data for user's merchant_id

### Messaging Tests

- [ ] DM between users in same merchant works
- [ ] DM between users in different merchants is blocked
- [ ] Channel messages are scoped to merchant
- [ ] Broadcast events only reach users in same merchant
- [ ] Mentions only work within same merchant

### Authentication Tests

- [ ] Login works for Admin users
- [ ] Login works for User (teacher/student) users
- [ ] Token includes merchant_id
- [ ] Token refresh maintains merchant_id
- [ ] Logout revokes token correctly

---

## 11. Security Rules

### Hard Requirements

1. **Never trust client-side merchant_id**
   - Always get from authenticated user
   - Never accept merchant_id from request body

2. **Always validate tenant access**
   - Check merchant_id before returning data
   - Check merchant_id before creating/updating records

3. **Use TenantScope trait**
   - Apply to all models that need tenant isolation
   - Use `withoutTenantScope()` only for admin operations

4. **Index merchant_id**
   - All tables with merchant_id must have index
   - Improves query performance

5. **Validate relationships**
   - When creating relationships, ensure both records have same merchant_id
   - Example: Adding user to channel - validate both have same merchant_id

---

## 12. Common Patterns

### Pattern 1: Get Current Merchant ID

```php
$merchantId = auth()->user()->merchant_id 
    ?? request()->attributes->get('merchant_id');
```

### Pattern 2: Query with Tenant Scope

```php
// Automatic (if TenantScope is used)
$channels = Channel::all(); // Only current merchant

// Explicit
$channels = Channel::forMerchant($merchantId)->get();
```

### Pattern 3: Create with Merchant ID

```php
$channel = Channel::create([
    'name' => $name,
    'merchant_id' => $merchantId, // Explicit
    // TenantScope will auto-set if not provided
]);
```

### Pattern 4: Validate Tenant Access

```php
$resource = Resource::find($id);

if ($resource->merchant_id !== auth()->user()->merchant_id) {
    abort(403, 'Unauthorized');
}
```

---

## 13. Troubleshooting

### Issue: "Merchant ID not found"

**Solution:**
1. Ensure user has `merchant_id` set
2. Ensure `TenantMiddleware` is applied to route
3. Check user model has `merchant_id` in fillable

### Issue: "Data from other tenant visible"

**Solution:**
1. Ensure model uses `TenantScope` trait
2. Check global scope is active
3. Verify queries don't use `withoutTenantScope()` incorrectly

### Issue: "Cannot create record - merchant_id required"

**Solution:**
1. Ensure `TenantMiddleware` is applied
2. Check `TenantScope` trait is used
3. Manually set `merchant_id` if needed

---

## 14. Future Enhancements

### School ID (Optional)

If a merchant has multiple schools:
- Add `school_id` column
- Scope by both `merchant_id` AND `school_id`
- Update `TenantScope` to handle both

### Multi-Database (Advanced)

For very large scale:
- Separate database per tenant
- Dynamic connection switching
- Requires more complex architecture

---

## Summary

**Key Points:**
1. ✅ Every table has `merchant_id` (required, indexed)
2. ✅ All models use `TenantScope` trait
3. ✅ All routes use `TenantMiddleware`
4. ✅ All queries automatically filter by `merchant_id`
5. ✅ All relationships validate same `merchant_id`
6. ✅ All broadcasts check `merchant_id`
7. ✅ All controllers validate tenant access

**Result:** Complete data isolation between schools/tenants. ✅
