#!/bin/bash

echo "🚀 Running Database Seeder..."
php artisan db:seed --quiet
echo "✅ Database seeded successfully!"
echo ""
echo "🔐 Login Credentials:"
echo "─────────────────────"
echo "👑 Super Admin: superadmin@test.com | password"
echo "👤 Admin: admin@test.com | password"  
echo "🧑‍🏫 Teacher: teacher@test.com | password"
echo "👨‍🎓 User: user@test.com | password"
echo ""
