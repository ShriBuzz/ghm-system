# Progress

Last updated: 2026-10-31. Branch in progress: `wp0/foundations`. Next: review and merge of pull request #1 into `main`; WP1 starts after the merge.

Step numbers follow `WP0-kickoff.md`. When a step's commit is pushed, mark it done here with the commit. Anything blocked goes under "Open questions".

## WP0 Foundations

| Step | Work | IDs | Status | Commit or note |
|---|---|---|---|---|
| 0 | Pre-checks and machine setup | | Done | Windows laptop: PHP 8.4, Composer, gh, MySQL 8.0. Mac: PHP 8.4, Composer 2.11, MySQL 8.4, Node 24 |
| 1 | Laravel 13 skeleton, Vite and Tailwind removed | | Done | `99eedf6` |
| 2 | Agent setup: Karpathy, Impeccable, Playwright, Ponytail, `/impeccable init` | | Done | `88ae807`, then the handoff commit (Ponytail settings, `PRODUCT.md`) |
| 3 | `.env.example` for MySQL, `config/ghm.php` | | Done | `7b82594`; unused `sessions` table (IP and user-agent columns) dropped in `f7b92b7` |
| 4 | Filament 5 panel at `/admin`, role enum, required MFA, admin seeder from `.env` | A1, S10 | Done | `91e3ff0` panel, `5be7456` Pest, `5c1aed3` roles and MFA. Walked in a browser: sign-in, forced MFA setup, code challenge |
| 5 | Login lockout (5 tries, 15 minutes), no notification polling, idle tab back to login | S2 | Done | `8ffab3a` login hardening. Walked in a browser: lockout message, no remember me, idle tab back at sign-in |
| 6 | Migrations, models, encrypted casts, enums, activity log | S3, S5, S6, A4 | Done | `f017803` case schema. Tables from plan section 6; S6 has its table only, the events come in WP2. P6 option lists wait on NBI (`docs/clarification-questions.md`, question 5) |
| 7 | `Grievance::visibleTo()`, `GrievancePolicy`, restriction observer, Pest tests per role | A1, A5 | Done | `9d39508` access rule. No case screens yet, so the browser check comes with WP2 |
| 8 | Security headers, HTTPS outside local | S1 | Done | `b5f9c74` security headers. Admin CSP found by browser walk; details in `docs/technical-note.md` |
| 9 | Public layout, `public/css/public.css`, language switch, `lang/en.json` and `lang/ne.json`, placeholder home | P2, S9 | Done | `51bfc6b` public shell. Walked at 360 px (JS on and off) and desktop in both languages; axe WCAG 2.2 AA clean |
| 10 | Pest, Larastan, Playwright config and specs, GitHub Actions CI | | Done | `7f4cfb8` tests and CI, `394660d` deterministic S2 spec. CI green: Pint, Larastan, Pest 81, Playwright 9. Pest came in step 4 |
| 11 | `docs/technical-note.md`, `docs/requirements-traceability.md` | | Done | `dba2494` traceability. S10 recovery-code test added on the way |
| 12 | Browser walk per role at 360 px and desktop | | Done | 2026-10-31: Playwright CLI sessions `-s=admin`, `-s=focal`, `-s=restricted` each signed in with password and authenticator code and reached the dashboard; `-s=public` checked `/ne` and `/en` at 360 px. Screenshots stayed on the developer machine, outside the repo |
| | `/ponytail-review` clean, pull request into `main` | | Done | Review's one must-fix fixed in `96f43f8`; pull request #1 (https://github.com/ShriBuzz/ghm-system/pull/1), CI green |

WP1 (public side), WP2 (admin side) and WP3 (alerts, reports, security) start only after the WP0 pull request is reviewed and merged.

## WP2 Admin side

Step numbers follow `WP2-kickoff.md`. WP1 is built in parallel by a teammate; step 1 is shared with it.

| Step | Work | IDs | Status | Commit or note |
|---|---|---|---|---|
| 1 | Reference number, PIN, attachment store (branch `wp2/shared-intake`) | P7, S4, S5 | Done | `App\Support\ReferenceNumber`, `Pin`, `AttachmentStore`; local disk `serve` turned off. Commit in this row once pushed |
| 2 | Staff panel in English, Filament info widget removed | A1 | Done | `SetPanelLocale` on the panel (persistent, so Livewire requests stay English). `PublicShellTest` "leaves the staff panel language alone" now expects English: a public `ne` cookie still does not change the panel. Commit in this row once pushed |
| 3 | Working-day count, nightly recount, `closed_at` | A2 | Done | `working_days_open` column, `App\Support\WorkingDays`, clock and `closed_at` in `GrievanceObserver`, `ghm:recount-working-days` daily at 00:30. Tried by hand on the local database and the step 2 panel language in a browser (sign-in error from a Livewire request in English). Commit in this row once pushed |
| 4 | Settings: categories, locations, holidays, users | A7, A5 | Done | Simple Filament resources (one page each, create and edit in a modal) under a Settings menu. `SettingsPolicy`: Administrator only, no delete; `HolidayPolicy` also allows delete. An Administrator cannot change their own role or deactivate themselves. `PlaceholderListsSeeder` outside production. Walked in a browser as Administrator and focal person (fake users, deleted after). The category restriction log entry moves to step 9 with the rest of the activity log. Commit in this row once pushed |
| 5 | Case list, search, filters, overdue badge | A2, A5 | Done | `GrievanceResource::getEloquentQuery()` is `Grievance::visibleTo()`, so list, counts, global search and record addresses share the one rule. Search by reference only (other text is encrypted). Filters: status, category, location, received dates, overdue. Days open sits next to the date: the browser walk found it scrolled out of view at 1280 px when it was the last column. View page holds reference and status until step 6; no create, edit or delete pages. `Status::open()` shared by the filter and the nightly recount. `signedInAs()` test helper moved to `tests/Pest.php`. Commit in this row once pushed |
| 6 | Case page: submission, status, priority, assign, restriction | A3, A4, A5 | Done | Submission in sections, contact block hidden for anonymous cases. Actions: status, priority, assign (`Grievance::assignableUsers()`: active users for whom `User::canSee()` is true), mark restricted (focal persons warned, then sent back to the list), remove restricted mark (Administrator only). `GrievancePolicy::view()` now calls `User::canSee()`. An assignee who loses sight of a case is cleared, from a case save and from a category flip. Walked in a browser as Administrator and focal person. Channel, recurrence, preferred contact and demographics show their stored codes until their enums exist (step 8, WP1). Commit in this row once pushed |
| 7 | Notes, replies, staff uploads, authorised download | A3, S4 | Not started | |
| 8 | Offline entry with channel, printable slip | A6 | Not started | |
| 9 | Activity log events and log page | S6 | Not started | |
| 10 | Playwright sign-in per role with seeded MFA | A1 | Not started | |
| 11 | Browser walk per role, `/ponytail-review`, traceability, request into `main` | | Not started | |

## Open questions for the project lead

Questions for NBI, with what to change once they answer, are in `docs/clarification-questions.md` (email due 2 Nov). Until then the code follows the assumptions listed there. The ones below are internal.

1. `laravel/pao` came with the Laravel skeleton and is agent tooling. Keep or remove? `CLAUDE.md` limits which agent tools the repo uses.
2. `main` is not protected on GitHub (checked 2026-10-31: no branch protection, no rulesets), although `CLAUDE.md` says it is. The repository is also public. Should `main` require a reviewed pull request, and should the repository be private?
3. The Nepali copy on public pages (site name, placeholder line) needs a Nepali speaker's read before UAT (P2 manual check).

## Decisions

Made so far (the commit messages have the detail):

- Commits for steps that no requirement ID covers use `chore(WP0)`.
- The skeleton's `CLAUDE.md` and `AGENTS.md` (Laravel Boost guidelines) were not kept.
- The Composer `dev` script was removed because `php artisan dev` starts Vite. Run `php artisan serve` instead.
- The welcome view is a plain placeholder until step 9.
- Impeccable: the hook is per machine (`.claude/settings.local.json`, ignored), the engine binary is ignored, telemetry is off in `.impeccable/config.json`.
- Ponytail is enabled for the project in `.claude/settings.json`, which also names its marketplace so a new machine is offered the plugin.
- `.cmd` files keep CRLF line endings.
- PHP 8.4 locally; CI will use 8.4 too.
- MFA is required for every user, as the kickoff says, although the RFQ marks two-factor login (S10) as Desirable.
- Local databases `ghm` (app) and `ghm_testing` (tests), user `ghm`.
- Pest came in at step 4, not step 10, because every requirement from step 4 on needs a Pest test. Feature tests refresh `ghm_testing` (MySQL) locally, and will in CI.
- MFA uses Filament's authenticator app with recovery codes, so a lost phone does not lock out the only Administrator. Secret and codes use the `encrypted` cast.
- Filament's default avatars come from ui-avatars.com, which would send each staff name to a third party. `InitialsAvatarProvider` draws them in the page instead.
- The admin seeder reads its values through `config('ghm.admin')`, because `env()` returns null once config is cached. It validates them (password at least 8 characters) and does nothing if the email already exists.
- The skeleton `DatabaseSeeder` no longer creates a test user, so `db:seed` is safe in production.
- Login lockout (S2): failed passwords are counted per email for 15 minutes; the fifth locks that email for 15 minutes from that failure, and attempts during the lock do not extend it. A successful sign-in clears the count. Keys use a SHA-256 hash of the trimmed, lower-cased email, never the IP. Risk, as planned: anyone who knows a staff email can lock that account for 15 minutes.
- Filament's own five-per-minute login throttle keyed on the IP; it is re-keyed on the email hash. Wrong MFA codes stay under Filament's per-user limiter.
- "Remember me" is removed from the login form: its cookie would sign a user back in after the 30-minute idle expiry.
- Filament database notifications are off (Filament's default), so nothing polls the server and keeps a session alive. A test guards this.
- Step 6 schema follows plan section 6. Status and priority are enums now (A4); channel, recurrence, preferred contact and message direction are plain strings until the forms that set them (WP1, WP2) define their enums. Category, location, incident date and recurrence are required columns, as P5 lists them; priority stays empty until staff set it.
- Enum labels use keys such as `status.received` in `lang/en.json` and `lang/ne.json`, so a wording change is a translation edit, not a code change.
- `users.is_active` (plan section 6): a deactivated user cannot sign in or open any panel page.
- Grievances, messages, notes and attachments use no `$fillable` yet; each form adds what it saves.
- An "I don't remember the exact date" incident date (plan section 6, P5) needs a precision flag; it is added in WP1 with the form.
- Step 7: `Grievance::visibleTo($user)` is the only visibility rule; `GrievancePolicy::view()` asks it, so the policy and the lists cannot drift apart. Every role may create (A6) and update the cases it sees; only the Administrator may remove a restriction; no policy method allows delete, so no one can delete a case.
- A category's restricted flag re-flags its cases with one bulk update, which skips model events. When WP2 adds the activity log (S6), it logs the category change itself.
- `DatabaseSeeder` runs `WithoutModelEvents`, which skips the restriction observer. A future seeder that creates cases must not rely on it.
- "Assignment only offers users who can see the case" (`CLAUDE.md`) is built with the assign field in WP2 (A3), using the same scope.
- Step 8: one global middleware handles S1. Public pages are self-only; the admin CSP adds a per-response script nonce, `'unsafe-eval'`, inline styles and `data:` images, each shown necessary by removing it during a browser walk. `technical-note.md` has the policy and the reasons.
- HTTPS is forced in every environment except `local` and `testing`, so staging behaves like production.
- Step 9, via `/impeccable shape`: the public shell is deliberately plain (system fonts with Devanagari fallbacks, neutral palette, one accent, all colours in CSS variables). The full visual-direction workshop runs in WP1 with the form, when more of the company's identity may be known. The header shows the service name only ("गुनासो सुनुवाइ" / "Grievance handling") until kick-off.
- WCAG 2.2 AA is the public-page target (confirmed 2026-10-31), beyond the RFQ's basic S9.
- Language switch (P2): each language has its own address, `/ne/...` and `/en/...`. The header shows both languages in their own script as a labelled nav, the current one marked with `aria-current`, the other a plain link with `lang` and `hreflang`, plus a `rel="alternate"` link in the head. Plain links need no form, no CSRF token and no session, work without JavaScript, keep Back and Forward in the right language, and let the company site or a QR poster link straight to one language. A `locale` cookie (a year, HttpOnly, SameSite=Lax) remembers the last language, so `/` redirects a returning visitor to it; a first visit goes to `/ne`. `SetLocale` runs on public routes only, so the staff panel keeps `APP_LOCALE` (question 14 in `docs/clarification-questions.md`).
- For WP1: switching language reloads the page, so text typed into the form would be lost. The form either keeps a draft with `public/js/form.js` or offers the language choice before the first field.
- Tests reset Livewire's static state before each test (`tests/Pest.php`); otherwise a Filament page rendered earlier in the same process makes Livewire inject its assets into the next public page. Real requests never carry it over.
- Step 10: Larastan at level 8 on application code (config files left out: the one level 7 finding is in the skeleton's `config/filesystems.php`). Playwright's mobile project covers public pages and the desktop project the admin panel. Admin specs that sign in (the S2 idle reload, role menus) need seeded TOTP users and come with step 12 and WP2.
- Playwright refuses to run anything but `@smoke` against a non-local `BASE_URL` unless `E2E_FULL=1`, so production is only ever smoke-tested.
- Step 12: every role sees only the Dashboard, because no role-specific screens exist yet; the role menus are checked again when WP2 adds them. The local role users (fake names, `@example.com`) were made through tinker, as planned.
- The dashboard still shows Filament's default info widget (its version, links to filamentphp.com and GitHub). WP2 replaces the dashboard content.
- From `/ponytail-review`: the profile page (`/admin/profile`) is on, so each user can change their own password (current one required) and manage MFA; its save throttle is keyed on the user, not the IP. After the first seed on a server, the `GHM_ADMIN_*` lines are deleted from `.env` and the config re-cached (`technical-note.md`).
- `config/session.php` and `config/cache.php` default to the `file` driver and a 30-minute session, so a production `.env` missing those lines still meets S2 and S5.
- Follow-ups from the review, not blocking: check the category's restricted flag at query time in `visibleTo()` too, closing a millisecond race when a category is restricted while one of its cases is being saved; remove Filament's info widget from the dashboard (WP2 replaces the dashboard).
- Branch `fix/A5-visibility-and-timezone`: `visibleTo()` now also checks the category's restricted flag at query time (the review follow-up above), and the app timezone is `Asia/Kathmandu`, so working days and dates follow Nepal time (A2).
- `public/js/admin-idle.js` reloads an admin tab when this tab has had no server response for the session lifetime. If the session has expired, the reload lands on the sign-in page; if another tab kept it alive, the page simply reloads.

Planned for the coming steps, as stated at kickoff. Raise any objection before they are built:

- The activity log records that an encrypted field changed, never its value.
- The step 12 role users are created locally with fake names through tinker, not a committed seeder.
- The language cookie is `locale`, with values `ne` and `en`, defaulting to `ne`.
