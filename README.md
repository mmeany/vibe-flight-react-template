# Flight React App

Monorepo template: **PHP Flight** API (`/backend`) + **React** SPA (`/frontend`), built into a single `dist/` directory for subdirectory deployment.

## What's included

- JWT auth (login, logout, optional public registration)
- User settings (theme, date format, display alias) stored server-side
- Admin user management and CSV import
- Empty dashboard shell with version footer
- `build.sh` — production bundle (default base path `/app/`)

## Starting a new project

Use the [`new-vibe`](https://github.com/mmeany/vibe-new-vibe) scaffold (recommended):

```bash
new-vibe   # installs from ~/bin; see vibe-new-vibe README
```

Or clone this template manually, then:

1. Set branding in `frontend/public/project.json` and `frontend/index.html`.
2. Set default deploy base in `build.sh` (`BASE=`) and match `project.json` → `link`.
3. Update favicons under `frontend/public/` and add the image path from `project.json` → `img`.
4. Copy `backend/.env.example` to `backend/.env` and configure MySQL + `JWT_SECRET`.
5. Build: `./build.sh --base /your-path/` (see [DEPLOY.md](DEPLOY.md)).

Cursor project skill: `.cursor/skills/mvm-flight-react/`.

## Development

**Terminal 1 — backend:**

```shell
cd backend
composer install
cp .env.example .env   # edit DB_* and JWT_SECRET
composer start
# http://localhost:8080
```

**Terminal 2 — frontend:**

```shell
cd frontend
npm install
npm run dev
# http://localhost:5173  (proxies /api to backend)
```

## First admin user

`ADMIN_USERNAMES` in `.env` grants access to the **Users** admin UI; it does not create accounts.

1. Set `REGISTRATION_ENABLED=true` in `.env`.
2. Register via the app (or use **Users** → Create after step 4).
3. Add your username to `ADMIN_USERNAMES` (comma-separated for multiple admins).
4. Log out and log back in so `is_admin` is set on the session.
5. Set `REGISTRATION_ENABLED=false` for production if you do not want public sign-up.

Admin create/import always works for allowlisted admins, regardless of `REGISTRATION_ENABLED`.

CSV template: [docs/admin-users.csv.example](docs/admin-users.csv.example). Required columns include `password_reminder` (breaking change vs older three-column CSVs).

### Settings, Help, and About

- **Settings** (`/settings`) — theme, date format, display alias, timezone, and self-service password change with password reminder.
- **Help** (`/help`) — in-app documentation (topic picker and prev/next navigation).
- **About** (`/about`) — app description and version; customize copy in `frontend/src/about/aboutContent.js`.

## Production build

```shell
./build.sh
# default: --base /app/

./build.sh --base /my-app/ --run-tests
```

Upload the **contents** of `dist/` to your host. See [DEPLOY.md](DEPLOY.md).

## Architecture notes

### User settings bundled in login response

`POST /api/v1/login` returns the JWT and `user.settings` in one response to avoid an extra round-trip on first load. To decouple later, add `GET /api/v1/settings` and stop including settings in the login payload.

### Settings on the server, JWT in localStorage

Preferences live in the `users.settings` JSON column. Only the auth token (and cached user) use localStorage, scoped by Vite `BASE_URL` — see `frontend/src/api/storage.js`.

### Public registration toggle

`REGISTRATION_ENABLED` in `.env` controls `POST /api/v1/register` and the sign-up UI. `GET /api/v1/config` exposes the flag to the frontend.
