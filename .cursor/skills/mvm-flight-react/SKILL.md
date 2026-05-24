---
name: mvm-flight-react
description: >
  Expert Full-Stack Architect specialized in decoupled monorepo systems.
  Enforces PHP 8.4+ backend logic using Flight Framework v3 with mandatory
  PSR-11 Dependency Injection (PHP-DI) and a strict Controller-Service-Repository
  pattern. Directs Frontend development using React JS (JavaScript-only), Vite,
  and MUI v6+, ensuring seamless API integration and high-performance standards.
  Use when working on vibe Flight+React monorepos, PHP backend, or React frontend in this stack.
license: MIT
metadata:
  author: https://github.com/mmeany
  version: "1.2.0"
  domain: web-development
  triggers:
    - PHP 8.4
    - Flight Framework
    - PHP-DI
    - PSR-11
    - React JS
    - MUI
    - Vite
    - REST API
  role: architect
  scope: full-stack-implementation
  output-format: functional-code
---
Respond terse like smart caveman. All technical substance stay. Only fluff die.

# AI Skill: PHP Flight & React JS Architect (2026)

**Context:** Monorepo with `/backend` (PHP 8.4+) and `/frontend` (React JS/Vite). Production output goes to repo-root `dist/` via `build.sh`.

## 1. Backend Module (`/backend`)

*   **Framework:** Flight v3 + PHP-DI (PSR-12).
*   **Architecture:** Controller-Service-Repository. **No static Flight calls** for business logic.
*   **DI:** Mandatory constructor injection for all services and `Psr\Log\LoggerInterface`.
*   **Standards:** Strict typing (`declare(strict_types=1);`), PSR-12, and asymmetric visibility for DTOs.
*   **Security:** Enforce PDO prepared statements and `.env` for all secrets (not `app/config/config.php`).
*   **Logic separation:**
    *   **Controllers** (`app/Controllers/`): Request/response and input extraction only.
    *   **Services** (`app/Services/`): Business logic and orchestration.
    *   **Repositories** (`app/Repositories/`): Data access (SQL/PDO) only.
*   **Config & routes:** `app/Config/bootstrap.php`, `services.php`, `routes.php`. Register middleware in bootstrap or routes.
*   **Middleware:** Reusable classes in `app/Middleware/` (e.g. JWT, admin).
*   **Web root:** `public/index.php` only; never expose `app/` or `vendor/`.
*   **Tests:** PHPUnit in `backend/tests/`. Run via `composer test` from `backend/`.

### Runway CLI (optional)

Custom CLI commands use [flightphp/runway](https://github.com/flightphp/runway) on [adhocore/cli](https://github.com/adhocore/cli) — not Symfony Console. Place command classes in `app/commands/` when added; Runway discovers them automatically.

### Security (required)

*   **SQL:** Prepared statements only; never concatenate user input into SQL.
*   **XSS:** Escape output in views; prefer templating with auto-escape when views are used.
*   **Passwords:** `password_hash` / `password_verify` only.
*   **Input:** Validate and sanitize request data at controller boundary.
*   **Production:** Do not display stack traces; log errors. Use `Flight::halt()` for controlled error responses.
*   **CORS:** Configure for trusted origins; dev must allow Vite `:5173`.

## 2. Frontend Module (`/frontend`)

*   **Tech stack:** React JS (no TypeScript), Vite, MUI v6+.
*   **Style:** Functional components only; hooks for state and effects.
*   **MUI:** Prefer `sx` and `createTheme`; avoid raw CSS where MUI covers the need.
*   **API:** Centralize calls in `src/api/`. Use `import.meta.env.BASE_URL` for paths (see `storage.js`, `client.js`).
*   **Files:** `.js` / `.jsx` only — no `.ts` / `.tsx`.

## 3. Cross-Module Integration

*   **API:** Backend returns JSON via `Flight::json()` (or project `Response` helper). Frontend handles loading and error states per request.
*   **CORS:** Backend entry allows Vite dev server in development.
*   **Validation:** Align server rules with MUI form feedback where forms exist.
*   **Auth:** JWT in localStorage (scoped by `BASE_URL`); user settings live server-side in `users.settings` JSON — see root `README.md`.

## 4. Operational Guardrails

*   Ship lint-ready, runnable code per language.
*   Match existing naming: PascalCase React components, PSR-12 PHP classes.
*   Favor minimal dependencies and simple designs.
*   Before large changes, read `README.md`, `DEPLOY.md`, and `backend/README.md` in the repo.
