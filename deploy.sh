#!/usr/bin/env bash
#
# BookTrips — Ploi deploy script
# ==============================
# Paste into: Ploi → Site → Repository → Deploy Script
# This file is versioned so changes are reviewable — keep the panel copy in sync.
#
# Assumes:
#   - site directory /home/ploi/booktrips.lk  (adjust if your Ploi site uses another path)
#   - branch: main
#   - Node 22 installed for the site (Ploi → Node.js)
#   - PHP 8.3 with gd, pdo_mysql, mbstring, curl, fileinfo and zip

set -e

cd /home/ploi/booktrips.lk

echo "==> Pulling the latest code"
# reset instead of pull: keeps the checkout identical even if a previous build
# wrote to a tracked file (git pull would stop with a conflict).
git fetch origin main
git reset --hard origin/main

echo "==> Installing PHP dependencies (no dev packages)"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

echo "==> Building frontend and SSR bundles"
npm ci --no-audit --no-fund
npm run build:ssr

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Caching config, routes and views"
php artisan optimize

echo "==> Restarting the SSR server and queue workers"
php artisan inertia:stop-ssr || true
php artisan queue:restart || true

echo "==> Ensuring the public storage symlink exists"
[ -L public/storage ] || php artisan storage:link

echo "==> Deploy finished"
