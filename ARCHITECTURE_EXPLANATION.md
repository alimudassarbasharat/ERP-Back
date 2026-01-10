# Multi-Tenant ERP Chat System - Architecture Explanation

## 🏗️ Simple Architecture (Like a 10-Year-Old Can Understand)

### The Big Picture
Imagine you have a big apartment building with many schools living in it:
- Each school is a "tenant" (they have their own `merchant_id`)
- Each school has rooms (channels) and people (users)
- Schools must NEVER see each other's data
- Each person can only enter rooms in their own school

### How It Works

#### 1. **Multi-Tenancy (Data Isolation)**
```
School A (merchant_id: "SCHOOL_A")
  ├── Users: Teacher1, Student1
  ├── Channels: Math Class, Science Class
  └── Messages: Only visible to School A

School B (merchant_id: "SCHOOL_B")
  ├── Users: Teacher2, Student2
  ├── Channels: English Class, History Class
  └── Messages: Only visible to School B
```

**Rule**: Every table that stores school data MUST have `merchant_id`
- `channels` table → has `merchant_id`
- `messages` table → has `merchant_id`
- `users` table → has `merchant_id`
- `direct_message_conversations` → has `merchant_id`

**How we enforce it**:
- `TenantScope` trait automatically adds `WHERE merchant_id = ?` to every query
- `TenantMiddleware` ensures every request knows which school it's for
- Database migrations ensure all tables have `merchant_id` column

#### 2. **User Roles (Who Can Do What)**
```
Super Admin (School Owner)
  └── Can do everything in their school

Faculty (Department Head)
  └── Can manage their department

Teachers
  └── Can teach and communicate with students

Students
  └── Can learn and communicate with teachers
```

**Implementation**: One `users` table + `roles` table (using Spatie Permission)
- Why? Scalable, easy to add new roles, clean separation
- Each user has `merchant_id` (which school they belong to)
- Roles are scoped per school (never cross boundaries)

#### 3. **Authentication (Login System)**
```
User logs in → Gets a token (like a key card)
Token is stored in browser (localStorage)
Every API request includes the token
Backend checks: "Is this token valid? Which school does it belong to?"
```

**The Bug We Fixed**:
- Before: Logging in as Teacher in incognito window broke SuperAdmin's session
- After: Each browser session keeps its own token (isolated)
- Tokens are stored per-browser, not globally shared

#### 4. **Channels (Group Chats)**
```
Channel = A room where multiple people can chat
- Channel has `merchant_id` (which school owns it)
- Users join channels (stored in `channel_users` pivot table)
- When user joins, they see channel in sidebar
- When new message arrives, unread count increases
```

**The Bug We Fixed**:
- Before: Channels existed but users couldn't see them in sidebar
- After: 
  - Channels are properly scoped by `merchant_id`
  - User's `channels()` relationship is tenant-scoped
  - Sidebar loads all channels user is member of (same merchant_id)

#### 5. **Direct Messages (1-on-1 Chat)**
```
User A sends message to User B
→ Message stored in database (with merchant_id)
→ Notification sent to User B (realtime via Reverb)
→ User B sees notification even if on another page
```

#### 6. **Realtime Updates (Laravel Reverb)**
```
User A sends message
→ Backend saves to database
→ Backend broadcasts event via Reverb
→ All connected users receive update instantly
→ No page refresh needed
```

**Channels**:
- `channel.{channelId}` → All members of this channel
- `dm.{conversationId}` → All participants of this DM
- `user.{userId}` → Private notifications for this user

#### 7. **System Messages**
```
Admin adds Teacher to channel
→ System message created: "Admin added Teacher to this channel"
→ Message stored like normal message (type='system')
→ All channel members see it
```

---

## 📁 Folder Structure

### Backend
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── ChannelController.php      # Channel CRUD, membership
│   │   │   ├── MessageController.php      # Channel messages
│   │   │   └── DirectMessageController.php # DM messages
│   │   └── Auth/
│   │       └── AuthController.php         # Login, logout, token refresh
│   └── Middleware/
│       └── TenantMiddleware.php           # Ensures merchant_id in requests
├── Models/
│   ├── Channel.php                         # Channel model (has TenantScope)
│   ├── Message.php                         # Channel message model
│   ├── DirectMessage.php                   # DM message model
│   ├── User.php                            # User model (has merchant_id)
│   └── MessageNotification.php             # Notification model
├── Events/
│   ├── MessageSent.php                     # Channel message event
│   ├── DirectMessageSent.php               # DM message event
│   ├── ChannelCreated.php                  # New channel event
│   └── UnreadCountUpdated.php              # Unread count change event
├── Services/
│   ├── UserService.php                     # User management
│   └── MediaService.php                    # File uploads
├── Helpers/
│   ├── MentionHelper.php                   # @mention parsing
│   └── ResponseHelper.php                  # API response formatting
└── Traits/
    └── TenantScope.php                     # Auto-scopes queries by merchant_id
```

### Frontend
```
src/
├── views/
│   └── messaging/
│       ├── MessagingApp.vue                # Main chat app
│       └── components/
│           ├── DirectMessageChat.vue       # DM chat component
│           └── CreateChannelModal.vue      # Create channel modal
├── stores/
│   └── auth.js                             # Auth state (tokens, user)
├── utils/
│   ├── axios.js                            # API client (with token interceptor)
│   └── echo.js                             # Reverb/Echo setup
└── composables/
    └── useAuth.js                          # Auth composable
```

---

## 🔐 Security & Tenant Isolation

### Database Level
- Every table has `merchant_id` column
- Indexes on `merchant_id` for performance
- Foreign keys ensure data integrity

### Application Level
- `TenantScope` trait auto-filters all queries
- `TenantMiddleware` validates merchant_id on every request
- Broadcast channel authorization checks merchant_id

### Frontend Level
- Tokens stored in localStorage (per-browser, isolated)
- Axios interceptor adds token to every request
- Echo (Reverb) uses token for channel authorization

---

## 🚀 How Data Flows

### Example: User Sends Message in Channel

1. **Frontend**: User types message, clicks send
2. **Frontend**: Axios sends POST `/api/messages/channels/{id}` with token
3. **Backend**: TenantMiddleware extracts merchant_id from token
4. **Backend**: MessageController validates user is channel member
5. **Backend**: Message saved to database (with merchant_id)
6. **Backend**: Unread count incremented for other members
7. **Backend**: Event broadcasted via Reverb: `MessageSent`
8. **Backend**: Response sent to sender
9. **Frontend**: All connected users receive event via Echo
10. **Frontend**: UI updates instantly (no refresh)

---

## ✅ What We Fixed

1. **Channel Visibility Bug**:
   - Added `merchant_id` to channels table (migration)
   - Fixed User->channels() relationship to be tenant-scoped
   - Fixed ChannelController to ensure tenant scoping

2. **Auth Token Isolation**:
   - Tokens stored per-browser (localStorage)
   - Each session isolated
   - No cross-session interference

3. **Multi-Tenancy**:
   - All tables have `merchant_id`
   - All queries are tenant-scoped
   - All broadcasts are tenant-scoped

---

## 🧪 Testing Checklist

- [ ] Login as SuperAdmin → See all channels
- [ ] Login as Teacher in incognito → SuperAdmin still works
- [ ] Create channel → All members see it
- [ ] Send message → All members receive it
- [ ] Add user to channel → System message appears
- [ ] Mute channel → No notifications
- [ ] Refresh page → Everything still works
- [ ] Multiple schools → No data leakage

---

This architecture ensures:
- ✅ Data never leaks between schools
- ✅ Each user sees only their school's data
- ✅ Realtime updates work smoothly
- ✅ System scales to hundreds of schools
- ✅ Code is clean and maintainable
