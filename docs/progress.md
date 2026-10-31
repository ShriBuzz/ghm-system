# Progress

Last updated: 2026-10-31. Branch in progress: `wp0/foundations`. Next: WP0 step 7.

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
| 6 | Migrations, models, encrypted casts, enums, activity log | S3, S5, S6, A4 | Done | `feat(S3,S5,A4,A1)` case schema. Tables from plan section 6; S6 has its table only, the events come in WP2. P6 value lists are open (question 1) |
| 7 | `Grievance::visibleTo()`, `GrievancePolicy`, restriction observer, Pest tests per role | A1, A5 | To do | Depends on step 6 |
| 8 | Security headers, HTTPS outside local | S1 | To do | |
| 9 | Public layout, `public/css/public.css`, language switch, `lang/en.json` and `lang/ne.json`, placeholder home | P2, S9 | To do | |
| 10 | Pest, Larastan, Playwright config and specs, GitHub Actions CI | | To do | Pest already added in step 4 |
| 11 | `docs/technical-note.md`, `docs/requirements-traceability.md` | | Started | Technical note started; the requirement list for traceability is in `docs/rfq-requirements.md` |
| 12 | Browser walk per role at 360 px and desktop | | To do | |
| | `/ponytail-review` clean, pull request into `main` | | To do | |

WP1 (public side), WP2 (admin side) and WP3 (alerts, reports, security) start only after the WP0 pull request is reviewed and merged.

## Open questions for the project lead

1. P6 value lists. Neither the RFQ nor the plan gives the options for complainant type, sex, age group, ethnicity and disability status, each with "prefer not to say", in both languages. The columns exist (encrypted text); the enums wait for the lists. Needed before the WP1 form.
2. The Nepali labels for A4 statuses and priorities are drafts, to be confirmed with NBI at kick-off.
3. `laravel/pao` came with the Laravel skeleton and is agent tooling. Keep or remove? `CLAUDE.md` limits which agent tools the repo uses.
4. `main` is not protected on GitHub (checked 2026-10-31: no branch protection, no rulesets), although `CLAUDE.md` says it is. The repository is also public. Should `main` require a reviewed pull request, and should the repository be private?
5. `PRODUCT.md` targets WCAG 2.2 AA, which goes beyond the RFQ's basic accessibility (S9). Keep it?
6. `PRODUCT.md` still has two product questions under "Open, not to be guessed": the company's name on public pages and emails, and what the print views are for. These matter from WP1.
7. `APP_NAME` is still the skeleton's `Laravel`. It shows as the panel brand, in page titles and as the account name in staff authenticator apps. What should it be?
8. Admin panel language. Filament follows `APP_LOCALE=ne`, but its Nepali translation is partial, so the MFA screens mix Nepali and English. Should staff get English, Nepali, or a switch?

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
- `public/js/admin-idle.js` reloads an admin tab when this tab has had no server response for the session lifetime. If the session has expired, the reload lands on the sign-in page; if another tab kept it alive, the page simply reloads.

Planned for the coming steps, as stated at kickoff. Raise any objection before they are built:

- The activity log records that an encrypted field changed, never its value.
- The admin CSP will probably need `'unsafe-eval'` and inline styles for Livewire and Alpine. Step 8 finds the minimum and records it in the technical note. The public CSP stays self-only.
- The step 12 role users are created locally with fake names through tinker, not a committed seeder.
- The language cookie is `locale`, with values `ne` and `en`, defaulting to `ne`.
