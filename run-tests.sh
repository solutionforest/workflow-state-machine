#!/bin/bash

echo "🚀 Running Workflow State Machine Tests..."

echo "📦 Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "🧪 Running Pest tests..."
./vendor/bin/pest --colors=always

echo "🎨 Running Pint code formatting check..."
./vendor/bin/pint --test

echo "🔍 Running PHPStan static analysis..."
./vendor/bin/phpstan analyse --no-progress --memory-limit=256M

echo "✅ All checks completed!"
