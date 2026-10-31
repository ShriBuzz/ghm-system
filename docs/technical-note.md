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

## Tests and CI

- Pest feature tests run against MySQL `ghm_testing`, each tagged with its requirement ID (`php artisan test --group=A5`).
- Larastan: `vendor/bin/phpstan analyse --memory-limit=1G`. Homebrew PHP's default 128M memory limit is too small for its worker processes. `parseModelCastsMethod` is on, so enum casts such as `role` and `status` are typed.
- Playwright (`playwright.config.ts`): a `mobile` project (360 px, touch) runs `tests/e2e/public.spec.ts`; a `desktop` project runs `tests/e2e/admin.spec.ts`. Specs carry requirement tags (`npx playwright test --grep @P2`). `BASE_URL` sets the target; locally Playwright starts its own `php artisan serve` on port 8001, apart from the usual 8000. When `BASE_URL` is not this machine, only the read-only `@smoke` specs run, unless `E2E_FULL=1` (staging), so the suite can never write to production.
- CI (`.github/workflows/ci.yml`) on every push and pull request: Pint, Larastan, Pest against a MySQL 8.4 service, then Playwright against `php artisan serve`. A failed run uploads the Playwright report.

## HTTPS and security headers (S1)

`App\Http\Middleware\SecurityHeaders` runs on every request.

- Outside `local` and `testing`, plain HTTP gets a 301 to HTTPS. Behind a proxy or load balancer that ends TLS, configure trusted proxies first, or every request looks like HTTP and the redirect loops.
- Over HTTPS: `Strict-Transport-Security: max-age=31536000`. Session and CSRF cookies are Secure over HTTPS because `SESSION_SECURE_COOKIE` is left unset; never set it to `false` in production. Cookies stay HttpOnly and SameSite=Lax (Laravel defaults).
- Every response: `Referrer-Policy: no-referrer`, `X-Content-Type-Options: nosniff`, and a Content Security Policy with `frame-ancestors 'none'`.
- Public pages: `default-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'`. No inline script or style, nothing from another host.
- Admin panel: `script-src 'self' 'nonce-…' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:`, otherwise as above. Each allowance was removed in turn during a full browser walk (sign-in, MFA setup, dashboard, sign-out) and each one broke the panel: the nonce covers Filament's and Livewire's inline scripts (a fresh one per response); `'unsafe-eval'` is needed by Alpine; Filament uses inline `style` attributes and `<style>` blocks without a nonce; the MFA QR code and avatars are `data:` images.
- Laravel's `/up` health page links a font on fonts.bunny.net and a script on cdn.jsdelivr.net. The public policy blocks both before any request leaves the browser; an uptime monitor only needs the 200.

## Agent tooling (not shipped)

- Impeccable 4.8.0, installed with `npx impeccable install --providers=claude --scope=project`. Skills and agents live in `.claude/`. The design hook sits in `.claude/settings.local.json`, which is per machine and not committed. The 20 MB engine binary is not committed either; the launcher downloads it on demand and checks it against `engine-checksums.txt`.
- `.impeccable/config.json` sets `"telemetry": false` for everyone. Impeccable still calls impeccable.style when it starts a new design, because that call is the feature; this happens on developer machines only, never from the application.
- Playwright CLI skill installed with `npx playwright cli install --skills` into `.claude/skills/playwright-cli`.

## Open points

- `laravel/pao` is agent tooling that came with the skeleton. `CLAUDE.md` limits which agent tools the repo uses, so keep or remove it is a decision for the project lead.
