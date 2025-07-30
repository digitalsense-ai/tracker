#!/bin/bash

# === Laravel Project Deployment Script ===
# This script assumes you are inside your Laravel project directory.

echo "📦 Pulling latest code from GitHub..."
git pull origin main || { echo "❌ Git pull failed"; exit 1; }

echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan view:clear
php artisan route:clear

echo "🛠 Running migrations..."
php artisan migrate --force || { echo "❌ Migration failed"; exit 1; }

echo "✅ Deployment complete!"