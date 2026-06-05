# ABN Construction Website

## 1) Requirements
- PHP 8.1+ with `mysqli`, `fileinfo`
- MySQL 8+
- Composer dependencies installed (`vendor/` already exists in this project)

## 2) Database setup (new machine)
Run this from project root:

```bash
mysql -u root -p < database/schema.sql
```

This creates:
- `building_blog` database
- `users`, `admin`, `blog_posts`, `projects`, `login_attempts` tables
- default admin account:
  - username: `admin`
  - password: `admin123`

Change this password immediately after first login.

If you already imported schema earlier, re-run the same command safely to add new tables.

## 3) Environment variables
`includes/config.php` now reads DB values from environment variables.

Example:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_USER=root
export DB_PASS=
export DB_NAME=building_blog
```

Optional (for forgot password email):

```bash
export SMTP_USER=your-email@gmail.com
export SMTP_PASS=your-gmail-app-password
export SMTP_FROM=your-email@gmail.com
```

Reference file: `.env.example`.

## 4) Access points
- Home: `index.php`
- Blog (enhanced): `blog.php`
- Projects (dynamic): `projects.php`
- User login: `includes/login.php`
- Admin login alias: `admin-login.php` (redirects to internal admin signin)
- Admin upload dashboard: `admin/dashboard.php` (admin role required)
- Admin projects manager: `admin/projects.php`

## 5) Upload feature
Admin can publish posts with:
- Images (`jpg`, `jpeg`, `png`, `webp`)
- Videos (`mp4`, `webm`, `mov`)
- Architectural drawings (`pdf`)

Files are stored in `uploads/blog/` and listed on `blog.php`.

Project media uploads are stored in `uploads/projects/` and listed on `projects.php`.

## 6) Security hardening in project
- Admin forms use CSRF tokens.
- Login includes brute-force throttling via `login_attempts`.
- Session handling is centralized in `includes/session_bootstrap.php` with secure cookie defaults.
- Upload folders include `.htaccess` rules to block execution of script-like files.
- Optional HTTPS enforcement:
  - set `FORCE_HTTPS=1` in `.env` (keep `0` on local `http://localhost`).
- Optional admin IP allowlist:
  - set `ADMIN_ALLOWED_IPS=127.0.0.1,::1` (comma-separated list).
  - If empty, IP restriction is disabled.
