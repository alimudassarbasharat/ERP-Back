<?php

/**
 * Comprehensive Reverb Setup Verification Script
 * Run: php verify-reverb-setup.php
 */

echo "=== Laravel Reverb Setup Verification ===\n\n";

$errors = [];
$warnings = [];

// 1. Check Composer Dependencies
echo "1. Checking Composer Dependencies...\n";
$composerJson = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);
if (isset($composerJson['require']['laravel/reverb'])) {
    echo "   ✅ laravel/reverb is installed: " . $composerJson['require']['laravel/reverb'] . "\n";
} else {
    $errors[] = "laravel/reverb is NOT installed in composer.json";
    echo "   ❌ laravel/reverb is NOT installed\n";
}

// 2. Check Environment Variables
echo "\n2. Checking Environment Variables...\n";
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    $errors[] = ".env file not found";
    echo "   ❌ .env file not found\n";
} else {
    $envContent = file_get_contents($envFile);
    
    $requiredVars = [
        'BROADCAST_DRIVER' => 'reverb',
        'REVERB_APP_ID' => null,
        'REVERB_APP_KEY' => null,
        'REVERB_APP_SECRET' => null,
        'REVERB_HOST' => 'localhost',
        'REVERB_PORT' => '8080',
        'REVERB_SCHEME' => 'http'
    ];
    
    foreach ($requiredVars as $var => $expected) {
        if (preg_match("/^{$var}=(.*)$/m", $envContent, $matches)) {
            $value = trim($matches[1]);
            if ($expected && $value !== $expected) {
                $warnings[] = "{$var} is set to '{$value}' but expected '{$expected}' for localhost";
                echo "   ⚠️  {$var} = {$value} (expected: {$expected})\n";
            } else {
                echo "   ✅ {$var} = {$value}\n";
            }
        } else {
            $errors[] = "{$var} is not set in .env";
            echo "   ❌ {$var} is not set\n";
        }
    }
}

// 3. Check Configuration Files
echo "\n3. Checking Configuration Files...\n";
$broadcastingConfig = config('broadcasting');
if ($broadcastingConfig['default'] === 'reverb') {
    echo "   ✅ BROADCAST_DRIVER is set to 'reverb'\n";
} else {
    $errors[] = "BROADCAST_DRIVER is not set to 'reverb' in config";
    echo "   ❌ BROADCAST_DRIVER is set to '{$broadcastingConfig['default']}'\n";
}

if (isset($broadcastingConfig['connections']['reverb'])) {
    echo "   ✅ Reverb connection is configured\n";
} else {
    $errors[] = "Reverb connection is not configured in broadcasting.php";
    echo "   ❌ Reverb connection is not configured\n";
}

// 4. Check BroadcastServiceProvider
echo "\n4. Checking BroadcastServiceProvider...\n";
$appConfig = config('app');
$providers = $appConfig['providers'] ?? [];
$hasBroadcastProvider = false;
foreach ($providers as $provider) {
    if (strpos($provider, 'BroadcastServiceProvider') !== false) {
        $hasBroadcastProvider = true;
        break;
    }
}
if ($hasBroadcastProvider) {
    echo "   ✅ BroadcastServiceProvider is registered\n";
} else {
    $errors[] = "BroadcastServiceProvider is not registered";
    echo "   ❌ BroadcastServiceProvider is not registered\n";
}

// 5. Check Routes
echo "\n5. Checking Broadcasting Routes...\n";
$channelsFile = __DIR__ . '/routes/channels.php';
if (file_exists($channelsFile)) {
    echo "   ✅ routes/channels.php exists\n";
    $channelsContent = file_get_contents($channelsFile);
    if (strpos($channelsContent, "dm.{conversationId}") !== false) {
        echo "   ✅ DM channel route is defined\n";
    } else {
        $warnings[] = "DM channel route may not be defined";
        echo "   ⚠️  DM channel route may not be defined\n";
    }
    if (strpos($channelsContent, "user.{userId}") !== false) {
        echo "   ✅ User channel route is defined\n";
    } else {
        $warnings[] = "User channel route may not be defined";
        echo "   ⚠️  User channel route may not be defined\n";
    }
} else {
    $errors[] = "routes/channels.php does not exist";
    echo "   ❌ routes/channels.php does not exist\n";
}

// 6. Check Reverb Server Connection
echo "\n6. Checking Reverb Server Connection...\n";
$reverbHost = 'localhost';
$reverbPort = 8080;

$connection = @fsockopen($reverbHost, $reverbPort, $errno, $errstr, 2);
if ($connection) {
    echo "   ✅ Reverb server is reachable on {$reverbHost}:{$reverbPort}\n";
    fclose($connection);
} else {
    $errors[] = "Reverb server is not reachable on {$reverbHost}:{$reverbPort}";
    echo "   ❌ Reverb server is NOT reachable on {$reverbHost}:{$reverbPort}\n";
    echo "      Error: {$errstr} ({$errno})\n";
    echo "      Make sure to run: php artisan reverb:start\n";
}

// Summary
echo "\n=== Summary ===\n";
if (empty($errors) && empty($warnings)) {
    echo "✅ All checks passed! Reverb is properly configured.\n";
    exit(0);
} else {
    if (!empty($warnings)) {
        echo "\n⚠️  Warnings:\n";
        foreach ($warnings as $warning) {
            echo "   - {$warning}\n";
        }
    }
    if (!empty($errors)) {
        echo "\n❌ Errors (must fix):\n";
        foreach ($errors as $error) {
            echo "   - {$error}\n";
        }
        exit(1);
    }
    exit(0);
}
