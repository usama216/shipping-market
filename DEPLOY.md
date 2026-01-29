# Marketsz cPanel Deployment Guide

## Package Creation (On Your Local Machine)

### 1. Build Frontend Assets
```bash
npm run build
```

### 2. Create Lightweight Deployment Package
```bash
# Create zip WITHOUT vendor folder (installer will download dependencies)
zip -r marketsz-deploy.zip . \
  -x "vendor/*" \
  -x "node_modules/*" \
  -x ".git/*" \
  -x "tests/*" \
  -x ".env" \
  -x "storage/logs/*" \
  -x "storage/framework/cache/data/*" \
  -x "storage/framework/sessions/*" \
  -x "storage/framework/views/*" \
  -x "*.log"
```

This creates a **~15-20MB package** instead of ~100MB+.

---

## cPanel Installation Steps

### 1. Upload & Extract
1. Login to cPanel → **File Manager**
2. Navigate to `public_html` (or your subdomain folder)
3. Upload `marketsz-deploy.zip`
4. Click **Extract**

### 2. Configure Document Root
Laravel's `public/` folder must be your web root. Choose one:

**Option A (Recommended): Subdomain Setup**
- Point your subdomain to `public_html/your-folder/public/`

**Option B: Move public files**
1. Move contents of `public/` to `public_html/`
2. Edit `public_html/index.php`:
   ```php
   require __DIR__.'/../your-folder/vendor/autoload.php';
   $app = require_once __DIR__.'/../your-folder/bootstrap/app.php';
   ```

### 3. Set Permissions
In File Manager, right-click these folders → **Change Permissions** → Set to `755`:
- `storage/`
- `bootstrap/cache/`

### 4. Create MySQL Database
1. cPanel → **MySQL Databases**
2. Create new database (e.g., `youruser_marketsz`)
3. Create new user with password
4. Add user to database with **ALL PRIVILEGES**

### 5. Run Web Installer
1. Visit: `https://yourdomain.com/install/`
2. Follow the 6-step wizard:
   - Welcome
   - Requirements Check
   - Database Configuration
   - **Dependencies** (auto-downloads Composer & installs packages)
   - Admin Account Creation
   - Complete!

---

## Post-Installation Security

After installation completes:
1. Delete or rename the `/install/` folder
2. The installer auto-creates `storage/installed` lock file to prevent re-runs

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "Composer install failed" | Check PHP memory limit (needs 256MB+). Contact host to increase. |
| "Permission denied" | Set `storage/` and `bootstrap/cache/` to 755 recursively |
| "Connection timed out" | Download may be slow. Wait or manually upload `vendor.zip` |
| "Class not found" errors | Clear cache: `php artisan config:clear` via SSH or cPanel Terminal |
