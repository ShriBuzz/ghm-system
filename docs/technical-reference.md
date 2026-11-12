# Technical reference

The detail behind the one-page technical note (`docs/technical-note.md`), for whoever maintains the system. Every Composer or npm package gets a one-line reason here when it is added.

## Platform

| Part | Version | Notes |
|---|---|---|
| PHP | 8.4.1 or newer | Required in `composer.json`, as the locked packages need it (Symfony 8, `spatie/laravel-activitylog` 5). `composer.json` also requires `gd` and `exif`, which redraw photos without their metadata. Every extension and `php.ini` setting a server needs: `docs/deploy.md`, section 1 |
| Laravel | 13.39 | From the `laravel/laravel` 13.x skeleton, with Vite and Tailwind removed |
| Admin panel | Filament 5.11, Livewire 4.4 | Staff panel at `/admin`; its assets are published on `composer install` |
| Database | MySQL 8 or MariaDB 10.6+ | |
| Node | 20+ | Dev and CI only (Playwright); nothing on the server needs Node |

## Composer packages

| Package | Kind | Why |
|---|---|---|
| `filament/filament` | runtime | The staff admin panel at `/admin`, with its built-in authenticator-app MFA. Its CSS, JS and the Inter font are published into `public/` by `filament:upgrade` on every `composer install` and are not committed; the font is served locally, so the panel makes no third-party requests. |
| `laravel/framework` | runtime | The application framework. |
| `openspout/openspout` | runtime | The CSV and Excel export (N4). Writes the rows as it reads the cases; Livewire then holds the whole file in memory to send it, fine for this project's few thousand cases a year. Writes the CSV with a byte-order mark so Excel reads Nepali text as UTF-8. |
| `spatie/laravel-backup` | runtime | The nightly backup (S7): database dump and attachments in an AES-256 password-protected zip, the 15-day cleanup and a daily check that a fresh backup exists. |
| `chillerlan/php-qrcode` | runtime | The QR poster (P10). Already installed with Filament's two-factor sign-in; declared because the app now uses it directly. Makes the code as SVG for print and PNG for download, and its decoder lets the test read the code back. |
| `spatie/laravel-activitylog` | runtime | The append-only activity log (S6). Its `activity_log` table has no IP or device columns. |
| `laravel/tinker` | runtime | Skeleton default; console REPL for local debugging. |
| `fakerphp/faker` | dev | Fake data for factories and tests; real grievance data is never used outside production. |
| `larastan/larastan` | dev | Static analysis (PHPStan for Laravel) at level 8 on `app/`, `bootstrap/app.php`, `database/` and `routes/`, run in CI. |
| `laravel/pail` | dev | Skeleton default; tails the local log. |
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

## Install

Server install, updates and the checks after each deploy: `docs/deploy.md`. Restoring a backup: `docs/restore.md`.

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
- The cache uses the `file` driver, which deletes an expired entry only when its key is read again. Sign-in counters are kept per email, so `ghm:prune-cache` deletes expired entries every hour, walking the folder without listing it first; otherwise a bot trying many emails would leave files behind until the host's file quota ran out (S2). A test run deleted 200,000 files with under 8 MB of memory.
- Changing `APP_KEY` signs every user out, as well as making encrypted fields unreadable; keep the old key in `APP_PREVIOUS_KEYS` when rotating.

## Public form (P1 to P9)

- The form is the home page, `/ne` and `/en`, and works with JavaScript off. `public/js/form.js` only adds conveniences: focus on the error summary, a size check before a 5 MB upload, and carrying typed answers across a language switch in this tab's `sessionStorage`, deleted as soon as they are put back.
- Spam (P9) needs no package: a hidden trap field, a minimum fill time read from an encrypted "form shown at" stamp, and an hourly limit per visitor. The plan's `spatie/laravel-honeypot` would have been a dependency for the same 30 lines. The limits are in `config/ghm.php` (`public`), from `GHM_SUBMISSIONS_PER_HOUR` (10), `GHM_FAILED_LOOKUPS_PER_HOUR` (10) and `GHM_MIN_FILL_SECONDS` (3); Playwright's local server raises them.
- Rate limits key on `App\Support\VisitorKey`, an HMAC of the IP with a random salt kept in the cache until midnight. The IP is never stored, and once the salt is gone no key can be turned back into an address (S5).
- A form sent after the 30-minute session ended: Laravel 13's `PreventRequestForgery` accepts a same-origin POST by the browser's `Sec-Fetch-Site` header, so current browsers send it normally. An older browser without that header fails the token check; `bootstrap/app.php` sends it back to the form with what was typed instead of "Page expired".
- The status page (P8) takes the reference and PIN by POST. 5 wrong PINs lock that reference for 15 minutes; failed lookups are capped per visitor; an unknown reference is checked against a dummy hash made once with the server's bcrypt cost (cached), so its timing gives nothing away. A correct PIN regenerates the session and keeps only the case ID, for 10 idle minutes. Complainant messages are capped at 10 an hour per grievance, since each emails staff. `pin` is in the exception handler's `dontFlash`, so a refused form never sends it back.
- `post_max_size` must be well above `upload_max_filesize` (`docs/deploy.md`): above it PHP drops the whole form, typed text included.
- The incident date (P5, U9) is Bikram Sambat on the Nepali form and AD on the English form; both are stored in AD, and `grievances.incident_date_calendar` (bs or ad) lets a month-only BS date show as its BS month. The BS selects have their own field names, so a language switch never carries a BS date into the AD selects. `App\Support\BsDate` converts with a table of BS month lengths, BS 2070 to 2090 (April 2013 to April 2034). The table comes from two open calendars that agree on every month, `nepali-date-converter` 3.4.2 (MIT, Subesh Bhandari) and `bikram-sambat` 1.8.1 (Apache-2.0, Medic), and was checked against the company's example 2083-07-22 = 2026-11-08; neither package is installed. Years past the government's latest published calendar are projections: when a new year's calendar is published, check its month lengths against the table, and add the years after 2090 before 2034.
- Staff screens show every date as BS with AD in brackets, such as 2083-07-22 (2026-11-08), from `BsDate::forStaff()`; a date outside the table shows in AD only. Date inputs (filters, offline entry, holidays, the summary range) take AD. The case page names each status and category with its Nepali beside the English (`Status::withNepali()`).
- The export gives each date twice, in its own columns so a spreadsheet can sort and filter either: AD with the Nepal time, then the BS date alone from `BsDate::format()`. Outside the table the BS cell is empty, so an old offline case never breaks an export.
- The complainant's description and messages keep their line breaks on the case page: the text is escaped first, then each line break becomes `<br />`, so anything typed as HTML stays visible text (Pest `CasePageTest`).

## Email and queue (N1, N2)

- Emails are queued in the `jobs` table, so a slow or failing mail server never blocks or breaks a submission. The server needs one cron line, `* * * * * php artisan schedule:run`; every minute it starts `queue:work --stop-when-empty --tries=4 --backoff=60,600,3600`, which exits once the queue is empty, never two at a time. A failed email is tried again 1 minute, 10 minutes and an hour later, so a mail-server outage or a host's hourly sending limit delays it rather than losing it. A job that fails four times stays in `failed_jobs` for 7 days, for `php artisan queue:retry`, then is pruned daily.
- The acknowledgement (N2, P7) carries the PIN, so its queued job is encrypted with `APP_KEY` (`ShouldBeEncrypted`). Staff alerts (N1) carry only the case ID.
- Mail servers quote the recipient's address when they refuse an email. Such an error becomes "An email could not be sent (mail server code …)" before it is logged (`bootstrap/app.php`) or stored in `failed_jobs` (`App\Support\FailedJobStore`, bound in `AppServiceProvider`), so a complainant's address never sits there in clear. To see a mail error in full, send a test email to your own address from `php artisan tinker`, for example `Mail::raw('test', fn ($m) => $m->to('you@company')->subject('test'))`: tinker shows the error on screen and nothing is logged.
- The mail driver is never `log`: that driver writes each email, recipient included, into `laravel.log`. Without a `MAIL_MAILER` line the app uses `smtp`, which fails visibly if unset. Locally `.env.example` uses `array`, which drops emails; tests read them from memory.

## Backups (S7)

- Every night at 01:00 `backup:run` dumps the database (`mysqldump`, which the host must provide) and copies `storage/app/private` without `livewire-tmp` into one zip, encrypted with AES-256 and `BACKUP_ARCHIVE_PASSWORD`. The code is in Git; `.env` is left out, so `APP_KEY` never sits next to the data it unlocks.
- Archives go to every disk in `BACKUP_DISKS`: `backups` (`storage/app/backups`, outside the web root) by default. The second copy off the web server is a disk added to that list once the host is known.
- `backup:clean` at 01:30 deletes archives older than 15 days, so the one from 14 days ago is always there (RFQ: at least 14). `backup:monitor` at 02:00 reports when the newest backup is older than a day.
- Failure emails go to `BACKUP_MAIL_TO`; without it nothing is sent. Success sends nothing.
- **Production must set `BACKUP_ARCHIVE_PASSWORD`.** Without it the archive is not encrypted and the attachments in it are readable. Keep it in the company's password manager with `APP_KEY`: a backup restores only with both.
- `php artisan about` shows under Backups whether the archive password is set; the deploy checks read it after every deploy.
- Restore steps: `docs/restore.md`.

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

- Logged: case created, viewed, status, priority, assignment (including clean-ups when an assignee can no longer see a case), manual restriction, note added, reply sent, message received, document uploaded and viewed, a category's restriction, a holiday added, changed or deleted, sign-in, and a failed sign-in to an existing account. Exports are logged when N4 is built.
- Entries hold codes, IDs and flags only, never case text or contact details. A holiday's entry also keeps its date and name, which are not personal data, so the Administrator can check the holidays focal persons enter. A failed sign-in is logged only when the email belongs to an account, and records only that account: never the email typed, the IP or the browser. An unknown email is not logged, because there is no one to record and the per-email throttle would not stop a script trying endless random ones.
- Entries are kept: the package's 365-day clean-up is not scheduled. How long to keep them is still to be agreed with the company.
