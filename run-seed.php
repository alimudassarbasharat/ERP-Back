<?php

// Simple seeder runner that bypasses Laravel's display formatting
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Set environment
$_ENV['APP_ENV'] = 'local';

echo "🚀 Starting database seeding...\n";

try {
    // Run the seeder directly without artisan formatting
    $seeder = new \Database\Seeders\CompleteSetupSeeder();
    $seeder->run();
    
    echo "✅ Database seeding completed successfully!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
