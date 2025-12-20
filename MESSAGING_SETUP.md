# Team Messaging System Setup Guide

This guide will help you set up the internal team messaging system (Slack-like chat) for your ERP project.

## Features

- **Channels**: Public and private channels for team communication
- **Direct Messages**: One-on-one and group direct messages
- **Real-time Updates**: Live messaging with typing indicators and presence
- **Message Features**: Reactions, thread replies, file attachments
- **User Presence**: Online/away/busy status indicators
- **Rich Text**: Message formatting with markdown support
- **Search**: Search messages across channels and conversations

## Backend Setup

### 1. Database Setup

Run the migrations to create all necessary tables:

```bash
php artisan migrate
```

### 2. Storage Setup

Create storage link for file uploads:

```bash
php artisan storage:link
```

### 3. Environment Configuration

Add these variables to your `.env` file for real-time messaging:

```env
# Broadcasting Configuration
BROADCAST_DRIVER=pusher
QUEUE_CONNECTION=sync

# Pusher Configuration (for real-time messaging)
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_APP_CLUSTER=your_pusher_cluster

# Or use Laravel WebSockets (free alternative)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

### 4. Install Broadcasting Dependencies

```bash
composer require pusher/pusher-php-server
```

For Laravel WebSockets (free alternative):
```bash
composer require beyondcode/laravel-websockets
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan migrate
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

### 5. Configure Broadcasting

Make sure broadcasting is enabled in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\BroadcastServiceProvider::class,
    // ...
],
```

## Frontend Setup

### 1. Install Dependencies

```bash
cd ERP-FrontEnd
npm install pusher-js laravel-echo
```

### 2. Environment Configuration

Create or update `.env` in the frontend:

```env
VUE_APP_PUSHER_APP_KEY=your_pusher_app_key
VUE_APP_PUSHER_APP_CLUSTER=your_pusher_cluster
```

### 3. Access the Messaging System

The messaging system is available at `/messaging` route when logged in.

## Initial Setup

### Create Default Channels

You can create some default channels by running this in tinker:

```bash
php artisan tinker
```

```php
// Create general channel
$channel = \App\Models\Channel::create([
    'name' => 'general',
    'description' => 'General discussion for the whole team',
    'type' => 'public',
    'created_by' => 1 // Admin user ID
]);

// Add all users to general channel
$users = \App\Models\User::all();
foreach ($users as $user) {
    $channel->addMember($user, $user->id === 1 ? 'admin' : 'member');
}

// Create announcements channel
$announcements = \App\Models\Channel::create([
    'name' => 'announcements',
    'description' => 'Important announcements',
    'type' => 'public',
    'created_by' => 1
]);
```

## API Endpoints

### Channels
- `GET /api/channels` - List all channels
- `POST /api/channels` - Create a new channel
- `GET /api/channels/{id}` - Get channel with messages
- `PUT /api/channels/{id}` - Update channel
- `POST /api/channels/{id}/join` - Join a channel
- `POST /api/channels/{id}/leave` - Leave a channel
- `POST /api/channels/{id}/members` - Add members to channel

### Messages
- `POST /api/messages/channels/{channelId}` - Send message to channel
- `GET /api/messages/thread/{messageId}` - Get message thread
- `PUT /api/messages/{id}` - Edit message
- `DELETE /api/messages/{id}` - Delete message
- `POST /api/messages/{id}/reactions` - Add reaction
- `DELETE /api/messages/{id}/reactions` - Remove reaction
- `GET /api/messages/search` - Search messages

### Direct Messages
- `GET /api/direct-messages/conversations` - List conversations
- `POST /api/direct-messages/conversations` - Start conversation
- `GET /api/direct-messages/conversations/{id}` - Get conversation messages
- `POST /api/direct-messages/conversations/{id}/messages` - Send direct message
- `PUT /api/direct-messages/messages/{id}` - Edit direct message
- `DELETE /api/direct-messages/messages/{id}` - Delete direct message

### Presence
- `POST /api/presence/status` - Update user status
- `GET /api/presence/online-users` - Get online users
- `POST /api/presence/typing` - Send typing indicator
- `POST /api/presence/heartbeat` - Keep user online

## Troubleshooting

### Real-time not working?

1. Check Pusher/WebSocket credentials in `.env`
2. Ensure broadcasting service provider is registered
3. Check browser console for WebSocket connection errors
4. Verify CORS settings allow WebSocket connections

### Messages not loading?

1. Check API authentication is working
2. Verify user has permission to access channels
3. Check browser network tab for API errors

### File uploads not working?

1. Check `php.ini` upload limits
2. Ensure storage directory is writable
3. Verify disk space is available

## Customization

### Themes

The UI uses CSS variables that can be customized in the Vue component:

```scss
$primary-color: #4a154b; // Your brand color
$hover-color: #f8f8f8;
$border-color: #e0e0e0;
$text-muted: #666;
```

### Features

You can enable/disable features by modifying the component data properties and API permissions.