<?php

echo "🚀 Laravel ERP Database Setup\n";
echo "═══════════════════════════════════\n\n";

echo "1️⃣ Running fresh migrations...\n";
system('php artisan migrate:fresh --force');

echo "\n2️⃣ Setting up database with essential data...\n";
system('php artisan db:seed --class=CompleteSetupSeeder');

echo "\n🎉 Setup completed successfully!\n";
echo "Your Laravel ERP system is ready to use.\n\n";
?>
