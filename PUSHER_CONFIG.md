# Pusher Configuration for Messaging System

## Backend Configuration (.env)

Add these lines to your Laravel `.env` file:

```env
# Broadcasting Configuration
BROADCAST_DRIVER=pusher

# For testing/development - use Laravel WebSockets (FREE)
PUSHER_APP_ID=local
PUSHER_APP_KEY=local123456
PUSHER_APP_SECRET=localsecret123
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

# For production - use actual Pusher credentials
# PUSHER_APP_ID=your_pusher_app_id
# PUSHER_APP_KEY=your_pusher_app_key
# PUSHER_APP_SECRET=your_pusher_app_secret
# PUSHER_APP_CLUSTER=your_cluster
```

## Frontend Configuration (.env)

Create a `.env` file in `ERP-FrontEnd` directory with:

```env
# API URL
VITE_API_URL=http://localhost:8000

# For testing/development
VITE_PUSHER_APP_KEY=local123456
VITE_PUSHER_APP_CLUSTER=mt1
VITE_PUSHER_HOST=127.0.0.1
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http

# For production
# VITE_PUSHER_APP_KEY=your_pusher_app_key
# VITE_PUSHER_APP_CLUSTER=your_cluster
```

## Option 1: Using Laravel WebSockets (FREE - Recommended for Development)

1. Install Laravel WebSockets:
```bash
composer require beyondcode/laravel-websockets
```

2. Publish configuration:
```bash
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

3. Run WebSocket server:
```bash
php artisan websockets:serve
```

4. Access dashboard at: http://localhost:8000/laravel-websockets

## Option 2: Using Pusher (For Production)

1. Create account at https://pusher.com
2. Create new app
3. Copy credentials to .env files
4. Update both backend and frontend .env files

## Testing the Setup

1. Open messaging at `/messaging`
2. Create a channel
3. Send a message
4. Open in another browser/incognito to test real-time updates

## Troubleshooting

If messages aren't updating in real-time:
1. Check browser console for WebSocket errors
2. Verify .env configurations match
3. Ensure WebSocket server is running (if using Laravel WebSockets)
4. Check CORS settings if on different domains