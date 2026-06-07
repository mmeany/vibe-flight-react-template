# Porting Guide — Public Site Features

> **Status:** These features are **implemented in this template** as the **Contact Us** flow (`POST /api/v1/contact`). To upgrade an older fork, follow **[guidelines_update.md](guidelines_update.md)**. This document remains the design reference and rationale.

Use this document when applying patterns from **just-for.fun** to another Flight + React template project. It covers eight feature areas: email (PHPMailer), Google Analytics, cookie consent, contact form, rate limiting, human check, Terms & Conditions, and Privacy Policy.

In this template the public form is named **Contact Us** (`POST /api/v1/contact`). The guideline’s `interest` naming maps to `contact` in code and routes.

---

## Stack assumptions

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.3+, Flight v3, PHP-DI, PDO/MySQL, Monolog |
| Frontend | React 19, Vite, MUI v6, React Router |
| Deploy | Monorepo build → Apache SPA + `/api/*` → `api/index.php` |

Architecture follows **Controller → Service → Repository** with env-driven config (`.env` at repo root).

---

## Libraries to add

### Backend (`composer.json`)

```json
"phpmailer/phpmailer": "^6.9"
```

Other backend deps used by these features (likely already in your template): `flightphp/core`, `php-di/php-di`, `vlucas/phpdotenv`, `monolog/monolog`, `psr/log`.

### Frontend (`package.json`)

```json
"vanilla-cookieconsent": "^3.1.0",
"react-router-dom": "^7.1.0",
"react-helmet-async": "^3.0.0"
```

GA4 is loaded via the official `gtag.js` script — no extra npm package.

---

## Environment variables

Add to `.env.example` and configure per environment:

| Variable | Purpose |
|----------|---------|
| `CHALLENGE_SECRET` | HMAC secret for signed math-challenge tokens |
| `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, `SMTP_PASS` | PHPMailer SMTP |
| `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME` | From address on outbound mail |
| `SMTP_AUTH` | Optional `true`/`false`; defaults to true when `SMTP_USER` is set |
| `DB_*` | MySQL for submissions + rate limits |
| `LOG_LEVEL` | `info` in production for request/phase trails |
| `CORS_ORIGINS` | Include Vite dev origin + production domain |
| `VITE_GA_MEASUREMENT_ID` | GA4 Measurement ID (e.g. `G-XXXXXXXX`) — production builds only |
| `VITE_SITE_URL` | Canonical base URL for SEO/legal pages |

**Vite note:** Set `envDir` in `vite.config.js` to the monorepo root so `VITE_*` vars are read from the shared `.env`.

**Local SMTP:** Mailpit/MailHog — `SMTP_HOST=127.0.0.1`, port `1025` or `2500`, `SMTP_SECURE=none`.

**WSL/Linux:** Use `DB_HOST=127.0.0.1` not `localhost` (socket mismatch).

---

## Feature 1 — Email service (PHPMailer)

### Decisions

- PHPMailer over raw `mail()` — configurable SMTP, TLS, timeouts, structured logging.
- **Save submission even if mail fails** — DB insert happens before SMTP; `auto_response_sent_at` set only on success.
- 15-second SMTP timeout; failures logged at `error`, submission still returns 201.
- Plain-text bodies (no HTML templates in v1).
- `SMTP_SECURE`: `tls` (STARTTLS, default), `ssl` (SMTPS), or `none` for local catchers.

### Files to port (backend)

| File | Role |
|------|------|
| `app/Services/MailService.php` | SMTP send, auto-ack + follow-up helpers |
| `app/Config/services.php` | Register `MailService` in DI |
| `tests/MailServiceTest.php` | Unit tests (mock or skip SMTP in CI) |

### Integration steps

1. `composer require phpmailer/phpmailer`
2. Copy `MailService.php`; wire in `services.php`.
3. Call from your form service after DB insert:
   - `sendAutoResponse($email, $knownAs, $category)` on public submit
   - `sendFollowUp(...)` if you add admin reply (optional)
4. Add SMTP vars to `.env.example`.
5. Customize subject/body strings and `categoryLabel()` match values for your form categories.

### Logging events

- `smtp.send_start` / `smtp.send_complete` with `duration_ms`
- `interest.smtp` (or equivalent) with `submission_id` and `sent` boolean

---

## Feature 2 — Google Analytics 4

### Decisions

- **Opt-in only** — GA script loads only after analytics cookie consent.
- **Consent Mode v2** — `analytics_storage: denied` by default; `granted` on accept; `ad_storage` always denied.
- **No GA on localhost** — `getMeasurementId()` returns `null` when `!import.meta.env.PROD`.
- **Manual pageviews** — `send_page_view: false` in config; SPA tracker fires `page_view` on route change.
- **Admin routes excluded** — no tracking on `/admin/*`.
- Custom events: form submit (category only, no PII), outbound link clicks (title + hostname).

### Files to port (frontend)

| File | Role |
|------|------|
| `src/cookieConsent/analytics.js` | Consent-gated gtag load, `trackPageView`, `trackEvent` |
| `src/components/AnalyticsRouteTracker.jsx` | SPA pageview on public routes |
| `src/components/InterestForm.jsx` (or Contact form) | `trackEvent('interest_form_submit', { category })` on success |
| `src/components/ProjectCards.jsx` (optional) | `trackEvent('project_outbound_click', ...)` pattern |

### Integration steps

1. Add `VITE_GA_MEASUREMENT_ID` to `.env` / `.env.example`.
2. Copy `analytics.js` and `AnalyticsRouteTracker.jsx`.
3. Mount `<AnalyticsRouteTracker />` in `App.jsx` (inside router).
4. Call `trackEvent(...)` from form and any trackable UI actions.
5. Create GA4 property; paste Measurement ID into env for production builds.
6. Update Privacy Policy and cookie consent copy to mention GA4 (see Feature 8).

### Public API (`analytics.js`)

```js
initAnalyticsIfConsented()  // called by cookie consent callbacks
trackPageView(path)
trackEvent(name, params)
isAnalyticsReady()
onAnalyticsReady(listener)
revokeAnalytics()           // on consent withdrawal
```

---

## Feature 3 — Cookie consent

### Decisions

- Library: **[vanilla-cookieconsent](https://github.com/orestbida/cookieconsent)** v3 — lightweight, no React wrapper required.
- Mode: **opt-in** (`mode: 'opt-in'`).
- Cookie name: `jff_consent` — **rename** for your project (e.g. `mysite_consent`).
- Revision: `CONSENT_REVISION = 2` — bump when policy changes to re-prompt users.
- Categories: `necessary` (read-only), `analytics` (opt-in), `marketing` (reserved, no scripts).
- `autoClearCookies` + regex patterns for `_ga`, `_gid`, `_gat` on analytics revoke.
- Banner hidden on admin routes; footer “Cookie preferences” link calls `showPreferences()`.
- Theme synced from MUI palette via CSS custom properties (`themeSync.js`).

### Files to port (frontend)

| File | Role |
|------|------|
| `src/cookieConsent/config.js` | Consent categories, callbacks, cookie settings |
| `src/cookieConsent/analytics.js` | Wired from `onConsent` / `onChange` |
| `src/cookieConsent/themeSync.js` | MUI → cookie banner theming |
| `src/cookieConsent/cookieConsent.css` | Import + layout overrides |
| `src/content/cookieConsentContent.js` | All user-facing consent copy + cookie tables |
| `src/components/CookieConsentManager.jsx` | Init, route-aware show/hide |
| `src/components/LegalFooter.jsx` | Privacy / Terms / Cookie preferences links |

### Integration steps

1. `npm install vanilla-cookieconsent`
2. Copy the `cookieConsent/` folder and related components.
3. Mount `<CookieConsentManager />` at app root (before routes).
4. Rename consent cookie (`config.js` → `cookie.name`).
5. Update `cookieConsentContent.js` — site name, cookie table, Privacy link.
6. Include `<LegalFooter />` on landing and legal pages.
7. When you change cookie/analytics behaviour, increment `CONSENT_REVISION` and update legal copy.

---

## Feature 4 — Contact / interest form

### Decisions

- Public **POST** endpoint stores submission in MySQL; returns 201 with thank-you message.
- Fields: firstname, surname, email, known_as, category (enum), question (max 250 chars).
- Frontend fetches challenge on mount; refreshes challenge after successful submit.
- Honeypot field `_website` hidden with `display: none` — any value → 422.
- Form copy and categories live in `frontend/src/content/siteContent.js` (or equivalent CMS file).
- API client: `fetch('/api/challenge')` + `fetch('/api/interest', { method: 'POST', body })`.

### API endpoints

| Method | Path | Auth | Response |
|--------|------|------|----------|
| `GET` | `/api/challenge` | Public | `{ question, token, form_loaded_at }` |
| `POST` | `/api/interest` | Public | 201 success / 422 validation / 429 rate limit |

Rename `/api/interest` → `/api/contact` in template if you prefer; keep controller/service naming consistent.

### Files to port

**Backend**

| File | Role |
|------|------|
| `app/Controllers/ChallengeController.php` | `GET /api/challenge` |
| `app/Controllers/InterestController.php` | `POST /api/interest` |
| `app/Services/InterestService.php` | Orchestrates validate → challenge → rate limit → DB → mail |
| `app/Services/ChallengeService.php` | Math challenge + timing |
| `app/Services/RateLimitService.php` | Rate limits |
| `app/Services/MailService.php` | Auto-ack email |
| `app/DTOs/SubmissionCreateDto.php` | Typed request payload |
| `app/Repositories/SubmissionRepository.php` | DB insert + `markAutoResponseSent` |
| `app/Repositories/RateLimitRepository.php` | Counter increments |
| `app/Support/ClientIp.php` | IP from `X-Forwarded-For` or `REMOTE_ADDR` |
| `migrations/001_create_submissions.sql` | Submissions table |
| `migrations/002_create_rate_limits.sql` | Rate limit table |
| `app/Config/routes.php` | Register routes |

**Frontend**

| File | Role |
|------|------|
| `src/components/InterestForm.jsx` | Form UI (adapt as ContactForm) |
| `src/api/interest.js` | API wrappers |
| `src/api/client.js` | Shared `apiRequest` helper |
| `src/content/siteContent.js` | Form heading, categories, labels |

### Submission flow (server)

```
validate fields + honeypot
  → challenge answer + 3s timing check
  → rate limits (transaction)
  → INSERT submission
  → send auto-ack email (non-blocking for response)
  → return 201
```

### Database — `submissions`

```sql
id, email, payload (JSON), ignored, follow_up_response,
created_at, auto_response_sent_at, follow_up_sent_at
```

`payload` stores non-email fields (firstname, surname, known_as, category, question).

### Customization checklist

- [ ] Rename endpoint and component if using “Contact Us”
- [ ] Adjust `VALID_CATEGORIES` in service + frontend dropdown options
- [ ] Update `MailService` email copy
- [ ] Align field names in DTO, controller body mapping, and frontend payload
- [ ] Add form section to landing page

---

## Feature 5 — Rate limiting

### Decisions

| Key | Window | Limit |
|-----|--------|-------|
| Email (lowercase) | minute | 1 |
| Email | hour | 3 |
| Email | lifetime | 10 |
| IP | hour | 5 (secondary/backstop) |

- Limits enforced in a **DB transaction** with `SELECT ... FOR UPDATE` to avoid races.
- Exceeded limits → `RuntimeException` → HTTP **429** with user-friendly message.
- Security events logged at `info` (`event: rate_limit`).
- No Redis — MySQL table is sufficient for low-volume personal sites.

### Files to port

- `app/Services/RateLimitService.php`
- `app/Repositories/RateLimitRepository.php`
- `migrations/002_create_rate_limits.sql`
- `tests/RateLimitServiceTest.php`

### Database — `rate_limits`

```sql
key_type ENUM('email','ip')
key_value VARCHAR(255)
window_type ENUM('minute','hour','lifetime')
count, window_start
UNIQUE (key_type, key_value, window_type)
```

Window reset: minute = 60s, hour = 3600s, lifetime = never resets.

### Tuning for your template

Edit constants in `RateLimitService.php`:

```php
private const EMAIL_LIMITS = ['minute' => 1, 'hour' => 3, 'lifetime' => 10];
private const IP_HOUR_LIMIT = 5;
```

---

## Feature 6 — Human check (anti-bot)

### Decisions

Three layers — **no reCAPTCHA/Turnstile**:

1. **Honeypot** — hidden `_website` field; bots that fill it are rejected (generic error, logged).
2. **Minimum submit time** — 3 seconds between challenge issue and submit (`form_loaded_at` must match token).
3. **Math challenge** — server generates `a + b`, signs payload with HMAC-SHA256 (`CHALLENGE_SECRET`).

Token format: `base64(json).hmac_signature` — tamper-proof, stateless (no server-side challenge store).

### Files to port

- `app/Services/ChallengeService.php`
- `app/Controllers/ChallengeController.php`
- `tests/ChallengeServiceTest.php`
- Frontend: challenge fetch + answer field + pass `challenge_token`, `challenge_answer`, `form_loaded_at` on submit

### Challenge API response

```json
{
  "question": "What is 7 + 4?",
  "token": "<signed>",
  "form_loaded_at": 1717776000
}
```

### Frontend pattern

- On mount: `GET /api/challenge` → store `challenge` state.
- On submit: include `challenge_token`, `challenge_answer`, `form_loaded_at` from challenge.
- Disable submit button until challenge loaded.
- After success: fetch fresh challenge.

### Security logging

`challenge_rejected` events: `timing_mismatch`, `submit_too_fast`, `malformed_token`, `invalid_signature`.

---

## Feature 7 — Terms & Conditions

### Decisions

- Static content in `frontend/src/content/legalContent.js` — not CMS-driven.
- Rendered via shared `LegalPage` layout component.
- Route: `/terms`
- SEO via `react-helmet-async` + `seoContent.terms`.
- Footer link on all public pages.
- Tone: personal/hobby site, no warranty, acceptable use, rate-limit abuse called out.

### Files to port

| File | Role |
|------|------|
| `src/content/legalContent.js` | `terms` sections array |
| `src/pages/TermsPage.jsx` | Thin page wrapper |
| `src/pages/LegalPage.jsx` | Shared layout (header, sections, footer) |
| `src/content/seoContent.js` | Title, description, canonical |
| `src/components/LegalFooter.jsx` | Footer links |
| `src/components/SeoHead.jsx` | Meta tags (if not already in template) |

### Integration steps

1. Add route `<Route path="/terms" element={<TermsPage />} />` in `App.jsx`.
2. Copy and **rewrite** `legalContent.terms` for your site name, form name, external links.
3. Add Terms link to footer.
4. Cross-reference Privacy Policy in a “Privacy” section.

---

## Feature 8 — Privacy Policy

### Decisions

- Same pattern as Terms: `legalContent.privacy` → `PrivacyPolicyPage` → `/privacy`.
- Must accurately describe:
  - Form data collected (fields + IP for rate limiting)
  - SMTP provider for email delivery
  - Hosting provider (generic “third-party web host” is fine)
  - **Cookie consent** (`jff_consent` — rename in copy)
  - **GA4** opt-in analytics and what events are tracked
  - Theme `localStorage` (no consent required)
  - Admin JWT in `localStorage` on `/admin` only (if your template has admin)
- `lastUpdated` date at top — bump when policy changes.

### Files to port

Same as Terms, plus `src/pages/PrivacyPolicyPage.jsx`.

### Keep in sync

When you change cookie categories, GA events, or form fields, update **both**:

- `legalContent.js` (Privacy sections)
- `cookieConsentContent.js` (cookie tables and descriptions)

Increment `CONSENT_REVISION` in `cookieConsent/config.js` when cookie behaviour changes.

---

## App wiring checklist

Use this ordered checklist when porting to a fresh template:

### Backend

- [ ] Run migrations (`submissions`, `rate_limits`)
- [ ] Register services in `services.php` (PDO, repositories, Mail, Challenge, RateLimit, Interest/Contact service)
- [ ] Add routes: `GET /api/challenge`, `POST /api/interest` (or `/api/contact`)
- [ ] Add `CHALLENGE_SECRET` and `SMTP_*` to `.env`
- [ ] Ensure CORS allows frontend origin
- [ ] Copy/adapt PHPUnit tests; run `composer test`

### Frontend

- [ ] `npm install vanilla-cookieconsent` (+ router/helmet if missing)
- [ ] Set `envDir` in Vite config to monorepo root
- [ ] Mount `CookieConsentManager`, `AnalyticsRouteTracker` in `App.jsx`
- [ ] Add routes: `/`, `/privacy`, `/terms`
- [ ] Add contact/interest form component to landing page
- [ ] Add `LegalFooter` to landing + legal pages
- [ ] Rename consent cookie and update all copy
- [ ] Set `VITE_GA_MEASUREMENT_ID` and `VITE_SITE_URL` for production

### Legal / compliance

- [ ] Rewrite Privacy Policy and Terms for your domain and data practices
- [ ] Align cookie consent tables with actual cookies
- [ ] Exclude admin routes from consent banner and analytics
- [ ] Verify GA does not load without consent (browser devtools → Network)

---

## HTTP status codes (contact submit)

| Code | Cause |
|------|-------|
| 201 | Success |
| 422 | Validation, honeypot, bad challenge, wrong math answer, too-fast submit |
| 429 | Rate limit exceeded |
| 500 | Uncaught server error (check `storage/logs/app.log`) |

---

## Optional extensions (out of scope in just-for.fun v1)

These were explicitly **not** implemented — consider separately for your template:

- reCAPTCHA / Cloudflare Turnstile
- Redis rate limiting
- HTML email templates
- Multi-admin
- Backend CMS for legal pages

---

## Source reference map

Quick index of where each concern lives in **just-for.fun**:

```
backend/
  app/Services/MailService.php          # PHPMailer
  app/Services/RateLimitService.php     # Rate limits
  app/Services/ChallengeService.php     # Human check
  app/Services/InterestService.php      # Form orchestration
  app/Controllers/InterestController.php
  app/Controllers/ChallengeController.php
  migrations/001_create_submissions.sql
  migrations/002_create_rate_limits.sql

frontend/
  src/cookieConsent/                    # Consent + GA integration
  src/components/CookieConsentManager.jsx
  src/components/AnalyticsRouteTracker.jsx
  src/components/InterestForm.jsx         # Contact form UI
  src/components/LegalFooter.jsx
  src/content/legalContent.js             # Privacy + Terms copy
  src/content/cookieConsentContent.js
  src/pages/PrivacyPolicyPage.jsx
  src/pages/TermsPage.jsx
  src/pages/LegalPage.jsx
```

---

## Suggested port order

1. **Database migrations** — submissions + rate_limits
2. **Human check + rate limiting** — backend only; test with curl/Postman
3. **Contact form API + frontend** — without email first
4. **PHPMailer** — auto-ack on success path
5. **Legal pages** — Privacy + Terms + footer
6. **Cookie consent** — wire before GA
7. **Google Analytics** — last, behind consent

This order lets you test the form end-to-end before adding compliance-sensitive tracking.
