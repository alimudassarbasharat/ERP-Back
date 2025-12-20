# Test Messaging System

## Quick API Test Commands

You can test the messaging API using these curl commands or Postman:

### 1. Get Channels (requires authentication token)
```bash
curl -X GET http://localhost:8000/api/channels \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 2. Create a Channel
```bash
curl -X POST http://localhost:8000/api/channels \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "test-channel",
    "description": "Test channel description",
    "type": "public"
  }'
```

### 3. Send a Message
```bash
curl -X POST http://localhost:8000/api/messages/channels/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "content": "Hello from API test!"
  }'
```

## Testing in Browser

1. **Login** to your ERP system
2. **Navigate** to `/messaging`
3. **Test Features:**
   - Click on #general channel
   - Type a message and press Enter
   - Click the emoji button to add reactions
   - Try @mentioning users
   - Upload a file using the paperclip icon

## Expected Results

✅ You should see:
- List of channels in sidebar
- Welcome message in #general
- Ability to send messages
- Message appears immediately after sending
- Can switch between channels
- Can start direct messages

## Troubleshooting

### If you see a blank page:
1. Check browser console (F12) for errors
2. Verify you're logged in
3. Check network tab for API errors

### If messages don't send:
1. Check Laravel is running: `php artisan serve`
2. Verify database tables exist: `php artisan migrate:status`
3. Check Laravel logs: `tail -f storage/logs/laravel.log`

### If no channels appear:
1. Run seeder: `php artisan db:seed --class=MessagingSeeder`
2. Check API response in network tab
3. Verify authentication token is sent

## Database Check

Run these queries to verify data:

```sql
-- Check channels
SELECT * FROM channels;

-- Check messages
SELECT * FROM messages;

-- Check channel members
SELECT * FROM channel_users;

-- Check users
SELECT id, name, email FROM users LIMIT 5;
```

## Success Indicators

✅ Channels load in sidebar
✅ Messages can be sent
✅ Messages appear in chat area
✅ Can switch between channels
✅ File upload button is visible
✅ User avatar/status shows correctly