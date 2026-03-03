# Verify and Fix Missing Users

## The Issue

The seeder ran successfully but didn't show user creation messages. This is normal - users are created silently using `firstOrCreate()`.

## Quick Fix - Run These Commands

### Step 1: Verify What Was Created

```bash
php artisan setup:verify
```

This will show you:
- ✓ If users exist
- ✓ If company settings exist
- ✓ If currencies exist
- ✓ If chart of accounts exists
- ✓ Default login credentials

### Step 2: Create Users (If Missing)

If users are missing, run:

```bash
php artisan users:create-defaults
```

This will create:
- Admin: `admin@codeacademy.ug` / `password`
- Accountant: `accountant@codeacademy.ug` / `password`

---

## Alternative: Check Manually via Database

### Option A: Via SSH/Command Line

```bash
# Check if users exist
php artisan tinker
```

Then in tinker:
```php
User::all();
// or
User::where('email', 'admin@codeacademy.ug')->first();
```

Press `Ctrl+C` to exit tinker.

### Option B: Via phpMyAdmin (Hostinger)

1. Go to Hostinger Control Panel
2. Open phpMyAdmin
3. Select your database
4. Click on `users` table
5. Check if you see:
   - `admin@codeacademy.ug`
   - `accountant@codeacademy.ug`

---

## If Users Don't Exist - Create Them

### Method 1: Run the Command (Recommended)

```bash
php artisan users:create-defaults
```

### Method 2: Run Seeder Again

```bash
php artisan db:seed --force
```

This is safe - it uses `firstOrCreate()` so won't duplicate data.

### Method 3: Create Manually via Tinker

```bash
php artisan tinker
```

Then:
```php
User::create([
    'name' => 'Admin User',
    'email' => 'admin@codeacademy.ug',
    'password' => bcrypt('password'),
    'role' => 'admin',
]);

User::create([
    'name' => 'Accountant User',
    'email' => 'accountant@codeacademy.ug',
    'password' => bcrypt('password'),
    'role' => 'accountant',
]);
```

Press `Ctrl+C` to exit.

### Method 4: Create via SQL (Last Resort)

Via phpMyAdmin, run this SQL:

```sql
INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES
('Admin User', 'admin@codeacademy.ug', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW(), NOW()),
('Accountant User', 'accountant@codeacademy.ug', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'accountant', NOW(), NOW());
```

**Note:** This password hash is for `password` - change it after login!

---

## Verify Login Works

1. Go to: `https://yourdomain.com/`
2. Should redirect to login page
3. Try logging in with:
   - Email: `admin@codeacademy.ug`
   - Password: `password`

4. If login works: ✓ Success!
5. If login fails: Check error logs at `storage/logs/laravel.log`

---

## Default Credentials

After verification, you should be able to login with:

| Email | Password | Role |
|-------|----------|------|
| admin@codeacademy.ug | password | admin |
| accountant@codeacademy.ug | password | accountant |

**⚠️ CRITICAL: Change these passwords immediately after first login!**

---

## Troubleshooting

### "User not found" when trying to login

**Cause:** Users weren't created

**Solution:**
```bash
php artisan users:create-defaults
```

### "Invalid credentials" when trying to login

**Possible causes:**
1. Wrong password (try: `password`)
2. Email typo (try: `admin@codeacademy.ug`)
3. Password hash issue

**Solution:**
```bash
# Reset password via tinker
php artisan tinker
```
```php
$user = User::where('email', 'admin@codeacademy.ug')->first();
$user->password = bcrypt('password');
$user->save();
```

### "Too many login attempts"

**Cause:** Rate limiting after failed attempts

**Solution:** Wait 5 minutes or clear cache:
```bash
php artisan cache:clear
```

---

## Quick Command Reference

```bash
# Verify setup
php artisan setup:verify

# Create default users
php artisan users:create-defaults

# Check users in database
php artisan tinker
>>> User::all();

# Reset a user's password
php artisan tinker
>>> User::where('email', 'admin@codeacademy.ug')->first()->update(['password' => bcrypt('newpassword')]);

# Clear all caches
php artisan cache:clear
php artisan config:clear
```

---

## After Successful Login

1. **Change passwords immediately:**
   - Go to Settings → Users
   - Edit each user
   - Set strong passwords

2. **Update email addresses:**
   - Change from `@codeacademy.ug` to your actual domain

3. **Create additional users:**
   - Add more users as needed
   - Assign appropriate roles

---

## Summary

The seeder likely created the users successfully, but didn't show output. Run:

```bash
php artisan setup:verify
```

This will tell you exactly what exists and what's missing. If users are missing, run:

```bash
php artisan users:create-defaults
```

Then try logging in with `admin@codeacademy.ug` / `password`.
