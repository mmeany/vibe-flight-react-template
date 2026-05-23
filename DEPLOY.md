# Deployment guide

This app is deployed as a single `dist/` bundle under a URL subdirectory (default: `/app/`). The frontend (React/Vite) and backend (PHP/Flight) share that directory; Apache `.htaccess` routes API traffic to `index.php` and everything else to the SPA.

## Prerequisites

**Build machine**

- Node.js and npm (frontend build)
- PHP 8.4+ and Composer (backend dependencies in `dist/`)
- Run from the repository root

**Production host (Apache shared hosting)**

- PHP 8.4+ with `pdo_mysql`
- Apache `mod_rewrite` enabled
- `AllowOverride` permits `.htaccess` in the deployment subdirectory
- MySQL/MariaDB on the same server as the web app

**Database**

- Create an empty database and a MySQL user with full privileges on that database before first deploy (migrations run automatically on first API boot).

## Build locally

```bash
./build.sh --base /app/
```

Optional flags:

- `--run-tests` — run frontend and backend tests before building
- `--base /other-path/` — if the URL path is not `/app/` (must include leading and trailing slashes, e.g. `/my-app/`)

Output is written to `dist/`. Upload the **contents** of `dist/` (not the `dist` folder itself) into the host directory that serves `https://<your-domain>/app/`.

Example: files land as `public_html/app/index.html`, `public_html/app/index.php`, etc.

## Configure production on the server

`build.sh` does **not** copy secrets. After upload, create `.env` in the deployment root (same directory as `index.php`):

```bash
cp .env.example .env
# Edit .env with production values
chmod 600 .env
```

| Variable | Production guidance |
|----------|---------------------|
| `APP_ENV` | `production` (disables Tracy debug bar) |
| `DB_HOST` | `127.0.0.1` (database on same host) |
| `DB_PORT` | `3306` |
| `DB_USERNAME` | MySQL user from hosting panel |
| `DB_PASSWORD` | Strong password |
| `DB_DATABASE` | Database name |
| `JWT_SECRET` | Long random string, e.g. `openssl rand -hex 32` |
| `JWT_EXPIRATION_DAYS` | e.g. `30` |
| `REGISTRATION_ENABLED` | `false` (recommended) — disables public sign-up; admins can still create users (see below) |
| `ADMIN_USERNAMES` | Comma-separated login usernames allowed to use **Users** admin UI, e.g. `mark` or `mark,jane` (exact match, case-sensitive) |
| `LOG_DIR` | `../../logs` (writes to `logs/app.log` under deploy root) |
| `LOG_LEVEL` | `ERROR` or `WARNING` |

Never commit `.env` to git.

### Log directory permissions

The build creates `logs/` with mode `755`. On shared hosting, ensure the PHP process user can write to `logs/`:

```bash
chmod 755 logs
# If needed:
chmod 775 logs
```

## What the build produces

| Path (under deploy root) | Purpose |
|--------------------------|---------|
| `index.html`, `assets/` | React SPA (built with Vite `base` matching `--base`) |
| `index.php` | PHP API entry |
| `app/`, `vendor/` | Backend code (blocked from direct web access) |
| `.htaccess` | Rewrites, SPA fallback, security denies |
| `.env.example` | Template for operators |
| `logs/` | Application logs |

Generated `.htaccess` (do not hand-edit on server; rebuild if the URL path changes):

- `RewriteBase` matches `--base`
- `/api/` → `index.php`
- Denies direct access to `.env`, `app/`, `vendor/`, `logs/`
- Other requests → static files or `index.html`

## Post-deploy checks

Replace `<domain>` with your host.

1. **SPA** — `https://<domain>/app/` loads the app (no blank page, no 404 on JS/CSS).
2. **API** — `https://<domain>/app/api/v1/...` returns JSON, not an HTML 404 page.
3. **Security** — `https://<domain>/app/.env` returns 403 Forbidden.
4. **Security** — `https://<domain>/app/app/` returns 403 Forbidden.
5. **Logs** — after a request, `logs/app.log` exists and is writable.
6. **Auth** — login works with a user in the database.
7. **Admin** — with `ADMIN_USERNAMES` set, admin login shows **Users**; creating a user via that page succeeds while public registration is disabled.

## Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| 404 on API routes | `RewriteBase` mismatch | Rebuild with `./build.sh --base /correct-path/` and re-upload |
| 404 on JS/CSS assets | Wrong Vite `base` at build | Same as above |
| 500 on first API hit | Missing `.env`, wrong DB credentials, or log dir not writable | Check `.env`, database exists, `logs/` permissions |
| Empty API / auth failures | `JWT_SECRET` empty or changed after tokens issued | Set `JWT_SECRET` in `.env`; users re-login |
| Database errors on boot | DB user cannot connect or DB missing | Fix credentials in `.env`; create database in panel |
| Tracy bar in production | `APP_ENV` not `production` | Set `APP_ENV=production` in `.env` |
| No **Users** menu after login | Username not in `ADMIN_USERNAMES`, or stale session | Add exact username to `.env`; log out and log in again |
| 403 on `/api/v1/admin/users` | Same as above | Fix `ADMIN_USERNAMES`; ensure `Authorization: Bearer` token is sent |
| 500 on login after deploy | PHP error in `AuthService` or migration | Check PHP/server error output and `logs/app.log` |

Check `logs/app.log` for migration and runtime errors.

## Admin users and provisioning accounts

When `REGISTRATION_ENABLED=false`, visitors cannot use the public **Sign Up** page. Allowlisted admins create accounts from the app instead.

### 1. Configure who is an admin

In production `.env`, set `ADMIN_USERNAMES` to the **username** each person uses to log in (not email):

```env
REGISTRATION_ENABLED=false
ADMIN_USERNAMES=mark
```

For multiple admins:

```env
ADMIN_USERNAMES=mark,jane
```

After changing `ADMIN_USERNAMES`, affected users must **log out and log in again** so the app receives `is_admin: true` on their session.

### 2. First admin account

`ADMIN_USERNAMES` only grants access to the admin UI; it does not create a user row. You still need one account in the database:

- **Before closing registration** — register via the public sign-up page, then set `REGISTRATION_ENABLED=false` and add that username to `ADMIN_USERNAMES`.
- **Fresh deploy with registration already off** — create the first user directly in MySQL (hashed password), or temporarily set `REGISTRATION_ENABLED=true`, register, then set it back to `false`.

Password hashes must use PHP `password_hash()` (bcrypt), same as the application.

### 3. Create users in the app (recommended)

1. Log in as an allowlisted admin.
2. Open **Users** in the header menu (visible only when `is_admin` is true).
3. Use one of:
   - **Create** — single user: username, email, password, optional alias.
   - **Import CSV** — batch upload. Required columns: `username`, `email`, `password`. Optional: `user_alias` (defaults to username if empty).
   - **Manage** — list, edit, deactivate (soft-delete), or restore users. Toggle **Show inactive** to see deactivated accounts.

Admin create/import **ignores** `REGISTRATION_ENABLED` and uses the same password rules as public registration (8+ characters, upper, lower, digit).

Example CSV template: [docs/admin-users.csv.example](docs/admin-users.csv.example). Keep real CSV files with plaintext passwords off the server and out of git.

### 4. Deactivated users

Deactivating a user sets `deleted_at`; they cannot log in. An admin can **Restore** from the manage tab. Admins cannot deactivate their own account while logged in.

### 5. Post-deploy checks (admin)

1. `.env` has your login username in `ADMIN_USERNAMES`.
2. Log in — **Users** appears in the nav.
3. Create a test user from **Create** or **Import CSV**.
4. Log in as the test user (optional).
5. Public `/register` shows “registration disabled” when `REGISTRATION_ENABLED=false`.

## Changing the URL subdirectory

1. Locally: `./build.sh --base /new-path/`
2. Re-upload the entire `dist/` contents
3. Update `.env` only if other settings change (path is baked into the build, not `.env`)

## Files you should not edit on the server

Change these in the repo, rebuild, and re-upload:

- `index.php`, `app/`, `vendor/`, compiled frontend assets, generated `.htaccess`

## Development vs production `.htaccess`

- [backend/public/.htaccess](backend/public/.htaccess) and [frontend/public/.htaccess](frontend/public/.htaccess) are dev samples only.
- Only `dist/.htaccess` from `build.sh` applies in production.

## Non-Apache hosts

This guide assumes Apache with `.htaccess`. For nginx or other servers, replicate:

- Subdirectory `base` for static files and SPA fallback
- Route `/app/api/` to `index.php`
- Deny access to `.env`, `app/`, `vendor/`, and `logs/`
