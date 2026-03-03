# Deployment Guide - Hostinger Shared Hosting

## Pre-Deployment Checklist

### 1. Files to Upload
- [ ] All application files (except those in .gitignore)
- [ ] `.env` file (create from `.env.example`)
- [ ] `composer.json` and `composer.lock`
- [ ] `package.json` and `package-lock.json`

### 2. Files/Folders NOT to Upload
- [ ] `node_modules/` (will be regenerated)
- [ ] `vendor/` (will be regenerated)
- [ ] `.git/` (optional, depends on your workflow)
- [ ] `.env` (create new one on server)
- [ ] `storage/logs/*` (will be created)
- [ ] `bootstrap/cache/*` (will be created)

---

## Step-by-Step Deployment Process

### STEP 1: Upload Files to Hostinger

1. **Connect via FTP/SFTP or File Manager**
   - Use Hostinger's File Manager or FTP client (FileZilla)
   - Upload all files to `public_html/` or your domain folder

2. **Important:** Laravel's `public` folder should be your web root
   - Option A: Upload everything to `public_html/` and move `public` folder contents to root
   - Option B: Upload app to `laravel/` and point domain to `laravel/public`

### STEP 2: Configure Environment File

1. **Create `.env` file** (copy from `.env.example`)

2. **Update these critical settings:**

```env
# Application
APP_NAME="Code Academy Uganda"
APP_ENV=production
APP_KEY=                          # Generate this - see Step 3
APP_DEBUG=false                   # MUST be false in production
APP_URL=https://yourdomain.com    # Your actual domain

# Database (Get from Hostinger)
DB_CONNECTION=mysql
DB_HOST=localhost                 # Usually localhost on shared hosting
DB_PORT=3306
DB_DATABASE=your_database_name    # From Hostinger MySQL panel
DB_USERNAME=your_database_user    # From Hostinger MySQL panel
DB_PASSWORD=your_database_pass    # From Hostinger MySQL panel

# Session & Cache
SESSION_DRIVER=database           # Use database for shared hosting
CACHE_STORE=database              # Use database for shared hosting
QUEUE_CONNECTION=database         # Use database for shared hosting

# Mail (Optional - configure later)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=your_email@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="info@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# File Storage
FILESYSTEM_DISK=public            # Use public disk for shared hosting
```

### STEP 3: Run Artisan Commands via SSH

**Connect to SSH** (if available on your Hostinger plan)

```bash
# Navigate to your application directory
cd public_html/

# 1. Generate Application Key (REQUIRED)
php artisan key:generate

# 2. Install Composer Dependencies (REQUIRED)
composer install --optimize-autoloader --no-dev

# 3. Run Database Migrations (REQUIRED)
php artisan migrate --force

# 4. Seed Database with Initial Data (REQUIRED for fresh install)
php artisan db:seed --force

# 5. Create Storage Link (REQUIRED for file uploads)
php artisan storage:link

# 6. Clear and Cache Configuration (REQUIRED)
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Set Permissions (REQUIRED)
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### STEP 4: If SSH is NOT Available

If your Hostinger plan doesn't have SSH access, you can:

1. **Generate APP_KEY locally:**
   ```bash
   php artisan key:generate --show
   ```
   Copy the key and paste it in your `.env` file on the server

2. **Install Composer Dependencies locally:**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```
   Then upload the `vendor/` folder via FTP

3. **Run Migrations via Web Route (Temporary):**
   
   Create a temporary file `setup.php` in your `public` folder:
   ```php
   <?php
   // REMOVE THIS FILE AFTER SETUP!
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';
   $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
   
   echo "Running migrations...\n";
   $kernel->call('migrate', ['--force' => true]);
   
   echo "Seeding database...\n";
   $kernel->call('db:seed', ['--force' => true]);
   
   echo "Creating storage link...\n";
   $kernel->call('storage:link');
   
   echo "Caching config...\n";
   $kernel->call('config:cache');
   $kernel->call('route:cache');
   $kernel->call('view:cache');
   
   echo "Setup complete! DELETE THIS FILE NOW!";
   ```
   
   Visit `https://yourdomain.com/setup.php` once, then **DELETE** the file immediately!

### STEP 5: Set File Permissions

Via File Manager or FTP:
- `storage/` → 755 (recursive)
- `bootstrap/cache/` → 755 (recursive)
- `.env` → 644 (readable only by owner)

### STEP 6: Configure Web Server

**For Apache (Hostinger uses Apache):**

Ensure `.htaccess` exists in your `public` folder:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## What Gets Created by Seeding

### 1. Default Users
```
Admin:
- Email: admin@codeacademy.ug
- Password: password
- Role: admin

Accountant:
- Email: accountant@codeacademy.ug
- Password: password
- Role: accountant
```

**⚠️ IMPORTANT:** Change these passwords immediately after first login!

### 2. Company Settings
- Company Name: Code Academy Uganda
- Currency: UGX (Ugandan Shilling)
- Timezone: Africa/Kampala
- Fiscal Year: Current year

### 3. Chart of Accounts (75+ accounts)
- **Assets:** Cash, Bank, Accounts Receivable
- **Liabilities:** Accounts Payable, Loans
- **Equity:** Owner's Equity, Retained Earnings
- **Income:** Program Fees, Donations, Grants
- **Expenses:** 65+ professional expense accounts (salaries, rent, utilities, etc.)

### 4. Currencies
- UGX (Ugandan Shilling) - Base currency
- USD (US Dollar)
- EUR (Euro)
- Exchange rates included

### 5. Sample Data (Optional)
- 2 Programs
- 2 Customers
- 2 Vendors
- Sample assets and budgets

---

## Post-Deployment Tasks

### 1. Security (CRITICAL)

```bash
# Change default user passwords
# Via web interface: Settings → Users → Edit

# Verify .env is not accessible
# Visit: https://yourdomain.com/.env
# Should show 403 Forbidden or 404 Not Found
```

### 2. Test Basic Functionality

- [ ] Can access login page: `https://yourdomain.com/`
- [ ] Can login with admin credentials
- [ ] Dashboard loads correctly
- [ ] Can create a test expense
- [ ] Can view journal entries
- [ ] Can view reports (Trial Balance, Balance Sheet)

### 3. Configure Company Settings

1. Go to Settings → Company Settings
2. Update:
   - Company name
   - Address
   - Phone
   - Email
   - Logo (upload your logo)
   - Fiscal year dates

### 4. Update Default Users

1. Go to Settings → Users
2. Change admin password
3. Change accountant password
4. Update email addresses
5. Create additional users as needed

### 5. Review Chart of Accounts

1. Go to Accounting → Chart of Accounts
2. Review all accounts
3. Add any missing accounts specific to your organization
4. Deactivate any accounts you don't need

### 6. Set Opening Balances (If Migrating from Another System)

1. Go to Accounting → Opening Balances
2. Enter opening balances for all accounts
3. Ensure debits = credits
4. Save opening balances

---

## Troubleshooting

### Issue: "500 Internal Server Error"

**Solutions:**
1. Check `.env` file exists and is configured correctly
2. Check `APP_KEY` is set in `.env`
3. Check database credentials are correct
4. Check file permissions (storage and bootstrap/cache must be writable)
5. Check error logs: `storage/logs/laravel.log`

### Issue: "No application encryption key has been specified"

**Solution:**
```bash
php artisan key:generate
```
Or manually add to `.env`:
```
APP_KEY=base64:your-generated-key-here
```

### Issue: "SQLSTATE[HY000] [1045] Access denied"

**Solution:**
- Verify database credentials in `.env`
- Ensure database exists in Hostinger MySQL panel
- Ensure user has permissions on the database

### Issue: "Class not found" errors

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue: "The stream or file could not be opened"

**Solution:**
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### Issue: Styles/CSS not loading

**Solution:**
1. Check `APP_URL` in `.env` matches your domain
2. Run: `php artisan config:cache`
3. Clear browser cache
4. Check if `public/build/` folder exists (run `npm run build` locally and upload)

---

## Optional: Build Assets Locally

If you need to rebuild CSS/JS:

```bash
# On your local machine
npm install
npm run build

# Upload the generated files
# Upload: public/build/ folder to server
```

---

## Maintenance Commands

### Clear All Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Rebuild Caches
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Check Application Status
```bash
php artisan about
```

---

## Backup Strategy

### What to Backup Regularly

1. **Database** (Daily)
   - Use Hostinger's MySQL backup tool
   - Or: `mysqldump -u username -p database_name > backup.sql`

2. **Uploaded Files** (Weekly)
   - `storage/app/public/receipts/`
   - `storage/app/public/logos/`
   - Any other uploaded files

3. **Environment File** (After changes)
   - `.env` file

---

## Performance Optimization

### 1. Enable OPcache (if available)
Check with Hostinger support if OPcache is enabled

### 2. Use Database for Sessions and Cache
Already configured in `.env`:
```env
SESSION_DRIVER=database
CACHE_STORE=database
```

### 3. Keep Caches Fresh
After any code changes:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong database password
- [ ] Changed default user passwords
- [ ] `.env` file not accessible via web
- [ ] `storage/` not accessible via web
- [ ] SSL certificate installed (HTTPS)
- [ ] Regular backups configured
- [ ] File permissions set correctly (755 for folders, 644 for files)

---

## Support Resources

- **Hostinger Support:** https://www.hostinger.com/support
- **Laravel Documentation:** https://laravel.com/docs
- **Application Logs:** `storage/logs/laravel.log`

---

## Quick Command Reference

```bash
# Essential Commands
php artisan key:generate              # Generate app key
php artisan migrate --force           # Run migrations
php artisan db:seed --force           # Seed database
php artisan storage:link              # Create storage link
php artisan config:cache              # Cache config
php artisan route:cache               # Cache routes
php artisan view:cache                # Cache views

# Maintenance Commands
php artisan down                      # Put in maintenance mode
php artisan up                        # Bring back online
php artisan cache:clear               # Clear all caches
php artisan config:clear              # Clear config cache

# Troubleshooting
php artisan about                     # Show app info
php artisan route:list                # List all routes
composer dump-autoload                # Regenerate autoload files
```

---

## Deployment Checklist Summary

### Before Deployment
- [ ] Test application locally
- [ ] Update `.env.example` with production settings
- [ ] Build assets: `npm run build`
- [ ] Run tests (if any)

### During Deployment
- [ ] Upload files to Hostinger
- [ ] Create and configure `.env` file
- [ ] Run `composer install`
- [ ] Run `php artisan key:generate`
- [ ] Run `php artisan migrate --force`
- [ ] Run `php artisan db:seed --force`
- [ ] Run `php artisan storage:link`
- [ ] Cache config: `php artisan config:cache`
- [ ] Set file permissions

### After Deployment
- [ ] Test login functionality
- [ ] Change default passwords
- [ ] Update company settings
- [ ] Review chart of accounts
- [ ] Set opening balances (if needed)
- [ ] Configure backups
- [ ] Test all major features
- [ ] Monitor error logs

---

**Estimated Deployment Time:** 30-60 minutes (depending on SSH availability)

**Need Help?** Check `storage/logs/laravel.log` for detailed error messages.
