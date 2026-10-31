# WP0 Foundations: kickoff prompt for Claude Code

Paste everything below the line into Claude Code, started in `F:\ghm-system`.

---

Read `CLAUDE.md` and `docs/implementation-plan.md` first. You are building work package 0 (Foundations) of the GHM system. Work on a new branch `wp0/foundations` created from `main`. Commit after each step with the requirement IDs in the message. Push the branch to `origin` and open a pull or merge request into `main` at the end. Never push to `main`.

## Before changing anything

1. Run `git status`, `git remote -v`, `git branch -a`, `php -v`, `composer -V`, `node -v`, `npm -v`, and check that a local MySQL or MariaDB server is reachable.
2. Stop and tell me if PHP is below 8.3, Node is below 20, MySQL is missing, or the working tree is not clean.
3. Tell me whether `origin` is GitHub or GitLab; it decides the CI file in step 10.
4. List your assumptions and anything unclear, then wait for my go-ahead.

## Steps

1. **Laravel 13 skeleton** in the repo root. `composer create-project` needs an empty folder, so create it in a temporary folder outside the repo and move the files in, keeping `.git`, `CLAUDE.md` and `docs/`. Remove Vite, Tailwind, `vite.config.js` and the default `resources/css` and `resources/js`; public pages use no build step.
2. **Agent setup, project scope only.**
    - Append the Karpathy guidelines from `https://raw.githubusercontent.com/multica-ai/andrej-karpathy-skills/HEAD/CLAUDE.md` below the marker at the end of `CLAUDE.md`.
    - `npx impeccable install --providers=claude --scope=project`, accept the design hook, then set `"telemetry": false` in `.impeccable/config.json`. Add Impeccable's recommended `.gitignore` block.
    - `npm install -D @playwright/test @playwright/cli @axe-core/playwright`, `npx playwright install chromium`, then `npx playwright cli install --skills` (no `-g`).
    - `.gitattributes`: mark `.claude/`, `.impeccable/`, `CLAUDE.md`, `PRODUCT.md` and `DESIGN.md` as `export-ignore`.
    - Then **stop** and ask me to run `/plugin marketplace add DietrichGebert/ponytail` and `/plugin install ponytail@ponytail` at project scope, restart the session so all skills load, and run `/impeccable init`. Continue after I confirm.
3. **Environment.** `.env.example` for MySQL with `SESSION_DRIVER=file`, `SESSION_LIFETIME=30`, `CACHE_STORE=file`, `QUEUE_CONNECTION=database`, `APP_LOCALE=ne`, `APP_FALLBACK_LOCALE=en`. `config/ghm.php` for SLA days (15), weekend days (Saturday), PIN length (6), upload limit (5 MB).
4. **Admin panel (A1, S10).** Filament 5 panel at `/admin`. `role` enum on users: `administrator`, `focal_person`, `restricted_officer`. Filament's built-in authenticator-app MFA, required for every user. Seeder for one Administrator using credentials from `.env`, never hard-coded.
5. **Login hardening (S2).** Custom Filament login page: 5 failed attempts lock that email for 15 minutes. Turn off Filament database-notification polling so idle sessions expire at 30 minutes, and add a small idle script that returns an idle admin tab to the login page.
6. **Schema (S3, S5, A4).** Migrations and models for `grievances`, `grievance_messages`, `grievance_notes`, `attachments`, `categories` (name_en, name_ne, is_restricted), `locations`, `holidays`, `reference_counters`, and the activity log (`spatie/laravel-activitylog`). Fields are listed in plan section 6. No IP or user-agent column anywhere. `encrypted` casts on name, phone, email, description, message body, note body, attachment original name and the demographic fields. Enums in `app/Enums` with the statuses and priorities exactly as RFQ A4 lists them, labels in both languages.
7. **Access rule (A1, A5).** `Grievance::visibleTo($user)` scope and `GrievancePolicy` following the role rules in `CLAUDE.md`. An observer keeps `is_restricted` equal to "category restricted OR manually restricted"; changing a category's flag re-flags its cases. Pest tests for every role against restricted and non-restricted cases.
8. **Security headers (S1).** Middleware sending HSTS, a strict self-only CSP on public routes, a Filament-compatible CSP on `/admin`, `Referrer-Policy: no-referrer`, `X-Content-Type-Options: nosniff` and `frame-ancestors 'none'`. Force HTTPS outside local.
9. **Public shell (P2, S9).** `resources/views/layouts/public.blade.php`, `public/css/public.css`, a language switch that sets a cookie and the page `lang` attribute, `lang/en.json` and `lang/ne.json` with matching keys, and a placeholder home page. No grievance form yet; that is WP1.
10. **Tests and CI.** Pest with the Laravel plugin, Larastan and Pint. `playwright.config.ts` with a `mobile` project (360 px wide, touch) and a `desktop` project, base URL from an environment variable, and one passing spec in each, one tagged `@smoke`. CI on every push: Pint, Larastan, Pest against MySQL, and Playwright against `php artisan serve`. Use `.github/workflows/ci.yml` for GitHub or `.gitlab-ci.yml` for GitLab.
11. **Docs.** Start `docs/technical-note.md` (framework, versions, every package and why) and `docs/requirements-traceability.md` with one row per requirement ID, filled for what WP0 covers.
12. **Validate in a browser.** With `php artisan serve` running, use Playwright CLI with one named session per role (`-s=admin`, `-s=focal`, `-s=restricted`): log in with MFA, confirm each role sees only its menu, and check the public shell at 360 px in both languages. Save screenshots outside the repo and tell me where.

## Done when

- `php artisan test`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` and `npx playwright test` pass locally and in CI.
- Each role can log in with MFA, and the A1 and A5 tests pass.
- A raw database read of an encrypted field shows ciphertext.
- `/ponytail-review` on the branch has no must-fix items.
- The pull or merge request from `wp0/foundations` into `main` lists the requirement IDs touched, anything skipped, and any risk I should know.

Do not start WP1 until I have reviewed and merged the request.
