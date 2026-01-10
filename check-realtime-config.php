<?php

/**
 * Realtime Messaging Configuration Checker
 * 
 * Run this script to verify your realtime messaging configuration:
 * php check-realtime-config.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== Realtime Messaging Configuration Check ===\n\n";

// Check 1: Broadcasting Driver
echo "1. Checking BROADCAST_DRIVER...\n";
$broadcastDriver = config('broadcasting.default');
if ($broadcastDriver === 'reverb') {
    echo "   ✅ BROADCAST_DRIVER is set to 'reverb'\n";
} else {
    echo "   ❌ BROADCAST_DRIVER is set to '{$broadcastDriver}' (should be 'reverb')\n";
    echo "   Fix: Set BROADCAST_DRIVER=reverb in .env file\n";
}

// Check 2: Reverb Configuration
echo "\n2. Checking Reverb Configuration...\n";
$reverbAppId = env('REVERB_APP_ID');
$reverbAppKey = env('REVERB_APP_KEY');
$reverbAppSecret = env('REVERB_APP_SECRET');
$reverbHost = env('REVERB_HOST', 'localhost');
$reverbPort = env('REVERB_PORT', 8080);
$reverbScheme = env('REVERB_SCHEME', 'http');

if ($reverbAppId && $reverbAppKey && $reverbAppSecret) {
    echo "   ✅ Reverb keys are configured\n";
    echo "      App ID: " . substr($reverbAppId, 0, 10) . "...\n";
    echo "      App Key: " . substr($reverbAppKey, 0, 10) . "...\n";
    echo "      Host: {$reverbHost}\n";
    echo "      Port: {$reverbPort}\n";
    echo "      Scheme: {$reverbScheme}\n";
} else {
    echo "   ❌ Reverb keys are missing\n";
    echo "   Fix: Run 'php artisan reverb:key' to generate keys\n";
    echo "   Then add them to .env file:\n";
    echo "   REVERB_APP_ID=...\n";
    echo "   REVERB_APP_KEY=...\n";
    echo "   REVERB_APP_SECRET=...\n";
}

// Check 3: Broadcasting Config
echo "\n3. Checking Broadcasting Config...\n";
$broadcastingConfig = config('broadcasting.connections.reverb');
if ($broadcastingConfig && isset($broadcastingConfig['driver']) && $broadcastingConfig['driver'] === 'reverb') {
    echo "   ✅ Reverb broadcasting connection is configured\n";
} else {
    echo "   ❌ Reverb broadcasting connection is not properly configured\n";
    echo "   Fix: Check config/broadcasting.php\n";
}

// Check 4: Channel Authorization
echo "\n4. Checking Channel Authorization Routes...\n";
$channelsFile = __DIR__ . '/routes/channels.php';
if (file_exists($channelsFile)) {
    $channelsContent = file_get_contents($channelsFile);
    if (strpos($channelsContent, "dm.{conversationId}") !== false) {
        echo "   ✅ DM channel authorization route exists\n";
    } else {
        echo "   ❌ DM channel authorization route not found\n";
    }
    
    if (strpos($channelsContent, "user.{userId}") !== false) {
        echo "   ✅ User channel authorization route exists\n";
    } else {
        echo "   ❌ User channel authorization route not found\n";
    }
} else {
    echo "   ❌ routes/channels.php not found\n";
}

// Check 5: BroadcastServiceProvider
echo "\n5. Checking BroadcastServiceProvider...\n";
$providers = config('app.providers');
$broadcastProviderFound = false;
foreach ($providers as $provider) {
    if (strpos($provider, 'BroadcastServiceProvider') !== false) {
        $broadcastProviderFound = true;
        break;
    }
}
if ($broadcastProviderFound) {
    echo "   ✅ BroadcastServiceProvider is registered\n";
} else {
    echo "   ❌ BroadcastServiceProvider is not registered\n";
    echo "   Fix: Add App\\Providers\\BroadcastServiceProvider to config/app.php providers array\n";
}

// Check 6: Event Classes
echo "\n6. Checking Event Classes...\n";
$directMessageSentFile = __DIR__ . '/app/Events/DirectMessageSent.php';
if (file_exists($directMessageSentFile)) {
    $eventContent = file_get_contents($directMessageSentFile);
    if (strpos($eventContent, 'ShouldBroadcastNow') !== false) {
        echo "   ✅ DirectMessageSent implements ShouldBroadcastNow (immediate broadcast)\n";
    } else {
        echo "   ⚠️  DirectMessageSent does not implement ShouldBroadcastNow\n";
        echo "   Note: ShouldBroadcastNow ensures immediate broadcast without queue\n";
    }
} else {
    echo "   ❌ DirectMessageSent event not found\n";
}

// Check 7: Reverb Server Status (if possible)
echo "\n7. Checking Reverb Server Status...\n";
$socket = @fsockopen($reverbHost, $reverbPort, $errno, $errstr, 2);
if ($socket) {
    echo "   ✅ Reverb server appears to be running on {$reverbHost}:{$reverbPort}\n";
    fclose($socket);
} else {
    echo "   ⚠️  Cannot connect to Reverb server on {$reverbHost}:{$reverbPort}\n";
    echo "   This might be normal if the server is not running\n";
    echo "   Start it with: php artisan reverb:start\n";
}

// Summary
echo "\n=== Summary ===\n";
echo "Configuration check complete. Review any ❌ or ⚠️  items above.\n";
echo "\nNext steps:\n";
echo "1. Fix any configuration issues\n";
echo "2. Clear config cache: php artisan config:clear && php artisan config:cache\n";
echo "3. Start Reverb server: php artisan reverb:start\n";
echo "4. Test messaging in browser\n";
echo "\n";
