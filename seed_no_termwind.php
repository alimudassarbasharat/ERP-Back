<?php

// Lightweight seeding entry that bypasses Artisan/Termwind output

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

// Bootstrap console kernel so facades/events are ready
/** @var Application $app */
$kernel = $app->make(ConsoleKernel::class);
$kernel->bootstrap();

// Run the standard database seeder without Artisan formatting
$seeder = $app->make(Database\Seeders\DatabaseSeeder::class);
$seeder->run();

echo "Seeded successfully (no-termwind).\n";


