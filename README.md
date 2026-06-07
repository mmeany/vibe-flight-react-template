# Flight React App

Monorepo template: **PHP Flight** API (`/backend`) + **React** SPA (`/frontend`), built into a single `dist/` directory for subdirectory deployment.

## What's included

- JWT auth (login, logout, optional public registration)
- User settings (theme, date format, display alias) stored server-side
- Admin user management and CSV import
- **Contact Us** form on the landing page (math challenge, rate limits, SMTP auto-ack)
- Admin **Submissions** UI (view, ignore, follow-up reply)
- Terms & Conditions and Privacy Policy (`/terms`, `/privacy`)
- Cookie consent (opt-in) and consent-gated Google Analytics 4
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
4. Copy `.env.example` to `.env` at the **repo root** and configure MySQL, `JWT_SECRET`, `CHALLENGE_SECRET`, and SMTP.
5. Build: `./build.sh --base /your-path/` (see [DEPLOY.md](DEPLOY.md)).

Cursor project skill: `.cursor/skills/mvm-flight-react/`.

## Development

**Terminal 1 — backend:**

```shell
cd backend
composer install
cp ../.env.example ../.env   # edit DB_*, JWT_SECRET, CHALLENGE_SECRET, SMTP_*
composer start
# http://localhost:8080
```

Use `DB_HOST=127.0.0.1` on WSL/Linux (not `localhost`). For local email testing, run [Mailpit](https://github.com/axllent/mailpit) and set `SMTP_HOST=127.0.0.1`, `SMTP_PORT=1025`, `SMTP_SECURE=none`.

**Terminal 2 — frontend:**

```shell
cd frontend
npm install
npm run dev
# http://localhost:5173  (proxies /api to backend)
```

## First admin user

`ADMIN_USERNAMES` in `.env` grants access to the **Users** and **Submissions** admin UIs; it does not create accounts.

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

## Template maintenance

This repo is a **starting point**, not a dependency. Apps you build from it are independent products; template changes do not flow in automatically.

### Starting a new app

Prefer one of these over `git clone` + deleting `.git` (same outcome, less cleanup):

| Method | Notes |
|--------|--------|
| [`new-vibe`](https://github.com/mmeany/vibe-new-vibe) | Recommended scaffold (see its README). |
| GitHub **Use this template** | Creates a new repo with no fork link to this template. |
| `gh repo create my-app --template <owner>/vibe-flight-react-template` | CLI equivalent of **Use this template**. |

Then complete [Starting a new project](#starting-a-new-project) (branding, `.env`, `build.sh` base path).

A GitHub **fork** is optional. It helps if you contribute fixes back here via pull request; it does **not** auto-update your app when this template changes.

### Linking an existing app to the template

In your app repo, add a read-only remote (name it `template` or `upstream`):

```shell
git remote add template https://github.com/<owner>/vibe-flight-react-template.git
git fetch template
```

Use this to inspect diffs or cherry-pick commits. Your deploy remote stays `origin` on **your** app repository.

### Pulling changes into an existing app

Choose based on how much the app has diverged:

| Situation | Approach |
|-----------|----------|
| Documented feature (routes, new files, small edits) | Follow [update.md](update.md) or [guidelines_update.md](guidelines_update.md) (copy files + apply diffs). Best when you customized the same areas. |
| One clean upstream commit you want as-is | `git fetch template && git cherry-pick <sha>` on a branch, resolve conflicts, run tests. |
| Staying close to the template | `git merge template/main` — expect conflicts once the app has its own features. |

There is no safe “always `git pull` from template” workflow for mature apps. Tag releases on this template (e.g. `v1.1.0`) and note the tag in each `update.md` guide when you add one.

**Examples:** public guest landing — [update.md](update.md); Contact Us, legal pages, cookie consent, GA4 — [guidelines_update.md](guidelines_update.md).

### Maintaining this template repo

When you ship a change that existing apps should adopt:

1. Merge to the default branch here.
2. Tag a release if the change is worth tracking (`git tag v1.x.x && git push --tags`).
3. Add or extend [update.md](update.md) or [guidelines_update.md](guidelines_update.md) with before/after routes, new files, and grep hints for forks.

## Architecture notes

### User settings bundled in login response

`POST /api/v1/login` returns the JWT and `user.settings` in one response to avoid an extra round-trip on first load. To decouple later, add `GET /api/v1/settings` and stop including settings in the login payload.

### Settings on the server, JWT in localStorage

Preferences live in the `users.settings` JSON column. Only the auth token (and cached user) use localStorage, scoped by Vite `BASE_URL` — see `frontend/src/api/storage.js`.

### Public registration toggle

`REGISTRATION_ENABLED` in `.env` controls `POST /api/v1/register` and the sign-up UI. `GET /api/v1/config` exposes the flag to the frontend.
