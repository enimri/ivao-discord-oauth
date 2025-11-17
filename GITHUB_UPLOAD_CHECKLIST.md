# GitHub Upload Checklist

Use this checklist to ensure you upload all necessary files and exclude sensitive data.

## ✅ Files Safe to Upload

### Core Application Files
- [x] `index.php` - Frontend entry point
- [x] `.htaccess` - URL rewriting rules
- [x] `clear_cache.php` - Cache clearing utility
- [x] `favicon.ico` - Site favicon

### Application Code
- [x] `app/` directory (all PHP files)
  - [x] `app/bootstrap.php`
  - [x] `app/config/config.php`
  - [x] `app/Controllers/` (all files)
  - [x] `app/Core/` (all files)
  - [x] `app/Models/` (all files)
  - [x] `app/Services/` (all files)

### Templates
- [x] `templates/` directory (all PHP template files)
  - [x] `templates/index.php`
  - [x] `templates/discord-join.php`
  - [x] `templates/maintenance.php`
  - [x] `templates/success.php`

### Assets
- [x] `assets/css/style.css`
- [x] `assets/img/background.jpg`
- [x] `assets/img/logo.png`

### Screenshots
- [x] `screenshots/welcome-page.jpg` - Welcome/Login page screenshot
- [x] `screenshots/discord-join-page.jpg` - Discord join page screenshot

### Backend (Discord Bot)
- [x] `backend/src/` (all Python source files)
- [x] `backend/requirements.txt`
- [x] `backend/setup.py`
- [x] `backend/start.sh`
- [x] `backend/docker/` (Docker configuration)
  - [x] `backend/docker/docker-compose.yml`
  - [x] `backend/docker/Dockerfile`
- [x] `backend/clear_cache.py`

### Configuration Templates
- [x] `env.example` - Environment variables template
- [x] `schema.sql` - Database schema (schema-only, no data)

### Documentation
- [x] `README.md` - Main documentation
- [x] `GITHUB_UPLOAD_CHECKLIST.md` - This file

## ❌ Files to EXCLUDE (Already in .gitignore)

### Sensitive Files
- [ ] `.env` - Contains real credentials (NEVER UPLOAD)
- [ ] `.env.local` - Local environment overrides
- [ ] `backend/.env` - Backend environment variables

### Log Files
- [ ] `backend/discord.log` - Contains user data and tokens
- [ ] Any `*.log` files

### Cache and Temporary Files
- [ ] `__pycache__/` directories
- [ ] `*.pyc` files
- [ ] `*.pyo` files
- [ ] `*.tmp` files

### Temporary Screenshots (excluded)
- [ ] `Screenshot*.jpg` - Temporary screenshot files (use `screenshots/` folder instead)
- [ ] `Screenshot*.png` - Temporary screenshot files

### IDE Files
- [ ] `.vscode/` directory
- [ ] `.idea/` directory
- [ ] `*.swp`, `*.swo` files

### OS Files
- [ ] `.DS_Store` (macOS)
- [ ] `Thumbs.db` (Windows)

### Database Files
- [ ] Any other `*.sql` files (except `schema.sql`)
- [ ] `*.db` files

## 🔍 Pre-Upload Verification

Before uploading, verify:

1. **No .env files exist** in the repository
   ```bash
   # Check for .env files
   find . -name ".env" -type f
   ```

2. **No hardcoded credentials** in code files
   - All secrets should come from environment variables
   - Check `app/config/config.php` - should use `$_ENV` or `getenv()`
   - Check `backend/src/config/settings.py` - should use `os.getenv()`

3. **SQL file is schema-only**
   - Open `schema.sql`
   - Verify no `INSERT INTO` statements with real data
   - Should only contain `CREATE TABLE` and `ALTER TABLE` statements

4. **Log files are excluded**
   - Check `backend/discord.log` exists but is in `.gitignore`

5. **Screenshots are excluded**
   - Personal screenshots should not be uploaded

## 📤 Manual Upload Steps (GitHub Web Interface)

1. **Go to GitHub**: https://github.com/new
2. **Create new repository**:
   - Name: `ivao-discord-auth` (or your preferred name)
   - Description: "IVAO Discord Authentication System"
   - Visibility: Choose Public or Private
   - **DO NOT** initialize with README, .gitignore, or license
3. **Upload files**:
   - Click "uploading an existing file"
   - Drag and drop all files from the checklist above
   - **DO NOT** upload files from the exclusion list
4. **Commit**:
   - Commit message: "Initial commit: IVAO Discord Authentication System"
   - Click "Commit changes"

## 🎯 Post-Upload Tasks

After uploading:

1. **Add repository description** on GitHub
2. **Add topics/tags** for discoverability:
   - `ivao`
   - `discord`
   - `oauth2`
   - `php`
   - `python`
   - `authentication`
3. **Update README** if needed with your specific division info
4. **Set up branch protection** (if needed)
5. **Add collaborators** (if needed)

## 🔒 Security Reminder

- **Never commit `.env` files**
- **Never commit log files with user data**
- **Never commit database dumps with real data**
- **Always use environment variables for secrets**
- **Review all files before uploading**

---

**Status**: ✅ Ready for upload
**Last Checked**: All files verified safe

