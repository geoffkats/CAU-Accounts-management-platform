# Quick Deployment Checklist - Hostinger

## ✅ Pre-Deployment (On Your Computer)

```bash
# 1. Build assets
npm run build

# 2. Test locally
php artisan migrate:fresh --seed
php artisan serve
# Test everything works

# 3. Prepare files
# - Ensure .gitignore is correct
# - Don't upload: node_modules, vendor, .env, .git
```

---

## ✅ Upload to Hostinger

### Via FTP/File Manager:
- [ ] Upload ALL files except: `node_modules/`, `vendor/`, `.env`, `.git/`
- [ ] Upload to: `public_html/` or your domain folder

---

## ✅ Configure on Server

### 1. Create `.env` File
Copy from `.env.example` and update:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=localhost
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_pass

SESSION_DRIVER=database
CACHE_STORE=database
```

---

## ✅ Run Commands (Via SSH or setup.php)

### Option A: Via SSH (Recommended)

```bash
cd public_html/

# REQUIRED COMMANDS (Run in order):
php artisan key:generate
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### Option B: No SSH? Use setup.php

1. Create `public/setup.php`:
```php
<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "1. Migrating...\n";
$kernel->call('migrate', ['--force' => true]);

echo "2. Seeding...\n";
$kernel->call('db:seed', ['--force' => true]);

echo "3. Storage link...\n";
$kernel->call('storage:link');

echo "4. Caching...\n";
$kernel->call('config:cache');
$kernel->call('route:cache');
$kernel->call('view:cache');

echo "DONE! DELETE THIS FILE NOW!";
```

2. Visit: `https://yourdomain.com/setup.php`
3. **DELETE setup.php immediately!**

---

## ✅ What Gets Created

### Default Users (Change passwords after login!)
```
Admin:
Email: admin@codeacademy.ug
Password: password

Accountant:
Email: accountant@codeacademy.ug
Password: password
```

### Database Content
- ✅ Company Settings (Code Academy Uganda)
- ✅ 75+ Chart of Accounts
- ✅ 3 Currencies (UGX, USD, EUR)
- ✅ Sample Programs, Customers, Vendors
- ✅ Sample Assets and Budgets

---

## ✅ Post-Deployment Tests

### 1. Basic Access
- [ ] Visit: `https://yourdomain.com/`
- [ ] Should redirect to login page (no welcome screen)
- [ ] Login with admin credentials
- [ ] Dashboard loads

### 2. Core Functions
- [ ] Create test expense
- [ ] View journal entries
- [ ] View Trial Balance
- [ ] View Balance Sheet

### 3. Security
- [ ] Change admin password
- [ ] Change accountant password
- [ ] Verify `.env` not accessible: `https://yourdomain.com/.env` (should be 403/404)

---

## ✅ Critical Settings to Update

### 1. Company Settings
- [ ] Company name
- [ ] Address, phone, email
- [ ] Upload logo
- [ ] Verify fiscal year dates

### 2. User Management
- [ ] Change all default passwords
- [ ] Update email addresses
- [ ] Create additional users

### 3. Chart of Accounts
- [ ] Review all accounts
- [ ] Add missing accounts
- [ ] Deactivate unused accounts

### 4. Opening Balances (If migrating)
- [ ] Go to: Accounting → Opening Balances
- [ ] Enter all opening balances
- [ ] Ensure debits = credits

---

## 🚨 Troubleshooting

### "500 Error"
```bash
# Check these:
1. .env file exists and configured
2. APP_KEY is set
3. Database credentials correct
4. Permissions: chmod -R 755 storage bootstrap/cache
5. Check logs: storage/logs/laravel.log
```

### "No encryption key"
```bash
php artisan key:generate
```

### "Database connection failed"
```
1. Check DB credentials in .env
2. Verify database exists in Hostinger panel
3. Test connection from Hostinger phpMyAdmin
```

### "Class not found"
```bash
composer dump-autoload
php artisan config:clear
```

### "Permission denied"
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

---

## 📋 File Permissions

```
Folders: 755
Files: 644
storage/: 755 (recursive)
bootstrap/cache/: 755 (recursive)
.env: 644
```

---

## 🔄 After Code Updates

```bash
# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# If database changes
php artisan migrate --force
```

---

## 💾 Backup (Setup Regular Backups!)

### Daily
- [ ] Database backup (via Hostinger panel)

### Weekly
- [ ] Files: `storage/app/public/`

### After Changes
- [ ] `.env` file

---

## ⏱️ Estimated Time

- **With SSH:** 15-30 minutes
- **Without SSH:** 30-45 minutes
- **First-time setup:** Add 15 minutes for testing

---

## 📞 Need Help?

1. Check: `storage/logs/laravel.log`
2. Hostinger Support: https://www.hostinger.com/support
3. Laravel Docs: https://laravel.com/docs

---

## ✅ Final Checklist

- [ ] Application accessible via domain
- [ ] Can login with admin account
- [ ] Dashboard loads correctly
- [ ] Can create transactions
- [ ] Reports work (Trial Balance, Balance Sheet)
- [ ] Default passwords changed
- [ ] Company settings updated
- [ ] `.env` not accessible via web
- [ ] SSL certificate active (HTTPS)
- [ ] Backups configured

---

**🎉 Deployment Complete!**

Remember to:
1. Change default passwords immediately
2. Update company information
3. Set up regular backups
4. Monitor error logs for first few days
