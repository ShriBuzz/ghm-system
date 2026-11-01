# Technical note

Started in WP0. Every Composer or npm package gets a one-line reason here when it is added.

## Platform

| Part | Version | Notes |
|---|---|---|
| PHP | 8.4 (8.3+ required) | Extensions: curl, fileinfo, intl, mbstring, openssl, pdo_mysql, sodium, zip |
| Laravel | 13.39 | From the `laravel/laravel` 13.x skeleton, with Vite and Tailwind removed |
| Admin panel | Filament 5.11, Livewire 4.4 | Staff panel at `/admin`; its assets are published on `composer install` |
| Database | MySQL 8 or MariaDB 10.6+ | |
| Node | 20+ | Dev and CI only (Playwright, Impeccable); nothing on the server needs Node |

## Composer packages

| Package | Kind | Why |
|---|---|---|
| `filament/filament` | runtime | The staff admin panel at `/admin`, with its built-in authenticator-app MFA. Its CSS, JS and the Inter font are published into `public/` by `filament:upgrade` on every `composer install` and are not committed; the font is served locally, so the panel makes no third-party requests. |
| `laravel/framework` | runtime | The application framework. |
| `spatie/laravel-activitylog` | runtime | The append-only activity log (S6). Its `activity_log` table has no IP or device columns. |
| `laravel/tinker` | runtime | Skeleton default; console REPL for local debugging. |
| `fakerphp/faker` | dev | Fake data for factories and tests; real grievance data is never used outside production. |
| `larastan/larastan` | dev | Static analysis (PHPStan for Laravel) at level 8 on `app/`, `bootstrap/app.php`, `database/` and `routes/`, run in CI. |
| `laravel/pail` | dev | Skeleton default; tails the local log. |
| `laravel/pao` | dev | Skeleton default; condenses test and Pint output when an AI agent runs them. Under review, see below. |
| `laravel/pint` | dev | Code style check (`vendor/bin/pint --test`). |
| `mockery/mockery` | dev | Test doubles. |
| `nunomaduro/collision` | dev | Readable console errors in tests. |
| `pestphp/pest` | dev | Test runner; each test is tagged with its requirement ID (`->group('P7')`). Runs against the MySQL `ghm_testing` database. |
| `pestphp/pest-plugin-laravel` | dev | Laravel helpers for Pest tests (HTTP, auth and database assertions). |
| `phpunit/phpunit` | dev | Test runner underneath Pest. |

## npm packages (dev and CI only)

| Package | Why |
|---|---|
| `@playwright/test` | Browser specs tagged by requirement ID (`@P7`), run locally and in CI. |
| `@playwright/cli` | Named browser sessions per role for walking user flows. |
| `@axe-core/playwright` | Accessibility checks inside Playwright specs. |

## First Administrator

`php artisan db:seed` creates the first Administrator from `GHM_ADMIN_NAME`, `GHM_ADMIN_EMAIL` and `GHM_ADMIN_PASSWORD`. Once it has run on a server, delete those three lines from `.env` and run `php artisan config:cache` again: `config:cache` copies them, password included, into `bootstrap/cache/config.php`. The Administrator then changes the password on the profile page (`/admin/profile`), where every user can change their own password (the current one is required) and manage MFA and recovery codes.

## Tests and CI

- Pest feature tests run against MySQL `ghm_testing`, each tagged with its requirement ID (`php artisan test --group=A5`).
- Larastan: `vendor/bin/phpstan analyse --memory-limit=1G`. Homebrew PHP's default 128M memory limit is too small for its worker processes. `parseModelCastsMethod` is on, so enum casts such as `role` and `status` are typed.
- Playwright (`playwright.config.ts`): a `mobile` project (360 px, touch) runs `tests/e2e/public.spec.ts`; a `desktop` project runs `tests/e2e/admin.spec.ts`, `cases.spec.ts` and `settings.spec.ts`. Specs carry requirement tags (`npx playwright test --grep @P2`). `BASE_URL` sets the target (`tests/e2e/target.ts`). Locally Playwright starts its own `php artisan serve` on port 8001, apart from the usual 8000, on the `ghm_testing` database (as in CI) and with the shipped `APP_LOCALE=ne`, whatever `.env` says; a global setup migrates that database and adds the placeholder lists. When `BASE_URL` is not this machine, only the read-only `@smoke` specs run, unless `E2E_FULL=1` (staging), so the suite can never write to production.
- Signed-in specs (A1): a spec sets `test.use({ role })`. Each Playwright worker signs in its own fake user per role (`e2e-<role>-<worker>@example.com`) on first use, with a password and a code computed from the authenticator secret, so parallel specs never share a server session; a shared session would lose one-time messages such as the slip's PIN, and an authenticator code is accepted only once. The session is saved in `playwright/.auth/` (ignored) and reused by later runs while the server accepts it, which keeps quick reruns under Filament's five sign-ins a minute. Users come from `php artisan ghm:e2e-user <role> <key> [--without-mfa]`, which gives the user a fresh random password and secret on every call and prints them as JSON, so no credential is kept in the repo. It runs in `local`, `testing` and `staging` (fake data only) and refuses production.
- Against staging: `BASE_URL=https://<staging host> E2E_FULL=1 E2E_ARTISAN="ssh <user>@<staging host> php <path>/artisan" npx playwright test`. `E2E_ARTISAN` is how to run artisan on the server under test; it defaults to `php artisan` on this machine. The staging deploy migrates and seeds its own database. The S2 idle spec is skipped there, because it needs the one-minute server below.
- S2 idle sign-out: Playwright starts a second local server on port 8002 whose sessions last one idle minute, so the idle spec waits about 75 seconds instead of 30 minutes. Its sessions live in cookies, so its session clean-up cannot delete the other servers' session files, including those of a dev server on 8000.
- CI (`.github/workflows/ci.yml`) on every push and pull request: Pint, Larastan, Pest against a MySQL 8.4 service, then Playwright against `php artisan serve`. A failed run uploads the Playwright report.

## HTTPS and security headers (S1)

`App\Http\Middleware\SecurityHeaders` runs on every request.

- Outside `local` and `testing`, plain HTTP gets a 301 to HTTPS. Behind a proxy or load balancer that ends TLS, configure trusted proxies first, or every request looks like HTTP and the redirect loops.
- Over HTTPS: `Strict-Transport-Security: max-age=31536000`. Session and CSRF cookies are Secure over HTTPS because `SESSION_SECURE_COOKIE` is left unset; never set it to `false` in production. Cookies stay HttpOnly and SameSite=Lax (Laravel defaults).
- Every response: `Referrer-Policy: no-referrer`, `X-Content-Type-Options: nosniff`, and a Content Security Policy with `frame-ancestors 'none'`.
- Public pages: `default-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'`. No inline script or style, nothing from another host.
- Admin panel: `script-src 'self' 'nonce-…' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:`, otherwise as above. Each allowance was removed in turn during a full browser walk (sign-in, MFA setup, dashboard, sign-out) and each one broke the panel: the nonce covers Filament's and Livewire's inline scripts (a fresh one per response); `'unsafe-eval'` is needed by Alpine; Filament uses inline `style` attributes and `<style>` blocks without a nonce; the MFA QR code and avatars are `data:` images. `blob:` was added in WP2 step 7: the document upload previews the chosen photo from a `blob:` address the page creates itself; without it the browser blocks the preview.
- Laravel's `/up` health page links a font on fonts.bunny.net and a script on cdn.jsdelivr.net. The public policy blocks both before any request leaves the browser; an uptime monitor only needs the 200.

## Sessions

- Session files are always encrypted with `APP_KEY`; `config/session.php` sets `encrypt` to `true` and no `.env` line can turn it off. The offline slip's PIN passes through the session once (A6, P7), and session files sit on the server's disk.
- Changing `APP_KEY` signs every user out, as well as making encrypted fields unreadable; keep the old key in `APP_PREVIOUS_KEYS` when rotating.

## Files and photos (P5, S4, S5)

`App\Support\AttachmentStore` takes every upload, staff and public.

- Accepted: JPEG, PNG, WebP and PDF up to `ghm.upload_max_kb` (5 MB). The type is read from the file's content, so a renamed program is refused.
- Files go to `storage/app/private/attachments/` under a random 40-character name; the original name is kept encrypted in `attachments.original_name`. The `local` disk's `serve` option is off, so Laravel registers no `/storage/...` route; files are reached only through the case-checked download route.
- Photos are redrawn with GD, which drops every metadata block (GPS position, phone model, time taken). The EXIF orientation is applied first when the `exif` extension is present; without it, a photo taken sideways stays sideways.
- Staff uploads in the panel go through Livewire's temporary folder (`storage/app/private/livewire-tmp`). The upload action deletes that copy and its `.json` (which holds the file name) as soon as `AttachmentStore` has stored or refused the file; otherwise Livewire would keep the original, metadata included, until a later upload, at least a day on, triggers its own clean-up. A file chosen in a dialog that is then cancelled is never handed to the action, so `ghm:clear-temporary-uploads` runs hourly and deletes anything in `livewire-tmp` older than a day, the same age rule as Livewire's. Such a file can still be there when the backup runs, so the backup (S7, WP3) must leave `livewire-tmp` out.
- Downloads go through `App\Http\Controllers\AttachmentController` at `/admin/attachments/{id}`: signed-in staff only, authorised by the case policy, always sent as a download under the original name.
- Known limit: PDF metadata (author name, creating software) is not removed. There is no light way to do it on shared hosting.

## Activity log (S6)

`spatie/laravel-activitylog` writes to `activity_log`; the Administrator reads it on the "Activity log" page, which cannot edit or delete entries.

- Logged: case created, viewed, status, priority, assignment (including clean-ups when an assignee can no longer see a case), manual restriction, note added, reply sent, message received, document uploaded and viewed, a category's restriction, sign-in, and a failed sign-in to an existing account. Exports are logged when N4 is built.
- Entries hold codes, IDs and flags only, never case text or contact details. A failed sign-in is logged only when the email belongs to an account, and records only that account: never the email typed, the IP or the browser. An unknown email is not logged, because there is no one to record and the per-email throttle would not stop a script trying endless random ones.
- Entries are kept: the package's 365-day clean-up is not scheduled. How long to keep them is clarification question 15.

## Agent tooling (not shipped)

- Impeccable 4.8.0, installed with `npx impeccable install --providers=claude --scope=project`. Skills and agents live in `.claude/`. The design hook sits in `.claude/settings.local.json`, which is per machine and not committed. The 20 MB engine binary is not committed either; the launcher downloads it on demand and checks it against `engine-checksums.txt`.
- `.impeccable/config.json` sets `"telemetry": false` for everyone. Impeccable still calls impeccable.style when it starts a new design, because that call is the feature; this happens on developer machines only, never from the application.
- Playwright CLI skill installed with `npx playwright cli install --skills` into `.claude/skills/playwright-cli`.

## Open points

- `laravel/pao` is agent tooling that came with the skeleton. `CLAUDE.md` limits which agent tools the repo uses, so keep or remove it is a decision for the project lead.
