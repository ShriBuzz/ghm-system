# Progress

Last updated: 2026-10-31. Branch in progress: `wp0/foundations`. Next: WP0 step 3.

Step numbers follow `WP0-kickoff.md`. When a step's commit is pushed, mark it done here with the commit. Anything blocked goes under "Open questions".

## WP0 Foundations

| Step | Work | IDs | Status | Commit or note |
|---|---|---|---|---|
| 0 | Pre-checks and machine setup | | Done | Windows laptop: PHP 8.4, Composer, gh, MySQL 8.0 |
| 1 | Laravel 13 skeleton, Vite and Tailwind removed | | Done | `99eedf6` |
| 2 | Agent setup: Karpathy, Impeccable, Playwright, Ponytail, `/impeccable init` | | Done | `88ae807`, then the handoff commit (Ponytail settings, `PRODUCT.md`) |
| 3 | `.env.example` for MySQL, `config/ghm.php` | | To do | |
| 4 | Filament 5 panel at `/admin`, role enum, required MFA, admin seeder from `.env` | A1, S10 | To do | |
| 5 | Login lockout (5 tries, 15 minutes), no notification polling, idle tab back to login | S2 | To do | |
| 6 | Migrations, models, encrypted casts, enums, activity log | S3, S5, S6, A4 | To do | A4 wording and draft Nepali labels are in `docs/rfq-requirements.md`; exact columns come from plan section 6. The kickoff tags this step S3, S5, A4; the activity log is S6 in the RFQ |
| 7 | `Grievance::visibleTo()`, `GrievancePolicy`, restriction observer, Pest tests per role | A1, A5 | To do | Depends on step 6 |
| 8 | Security headers, HTTPS outside local | S1 | To do | |
| 9 | Public layout, `public/css/public.css`, language switch, `lang/en.json` and `lang/ne.json`, placeholder home | P2, S9 | To do | |
| 10 | Pest, Larastan, Playwright config and specs, GitHub Actions CI | | To do | |
| 11 | `docs/technical-note.md`, `docs/requirements-traceability.md` | | Started | Technical note started; the requirement list for traceability is in `docs/rfq-requirements.md` |
| 12 | Browser walk per role at 360 px and desktop | | To do | |
| | `/ponytail-review` clean, pull request into `main` | | To do | |

WP1 (public side), WP2 (admin side) and WP3 (alerts, reports, security) start only after the WP0 pull request is reviewed and merged.

## Open questions for the project lead

1. `docs/implementation-plan.md` is not in the repo. Step 6 takes its exact columns from plan section 6. The RFQ wording is in `docs/rfq-requirements.md`, which wins if the two disagree.
2. The Nepali labels for A4 statuses and priorities are drafts, to be confirmed with NBI at kick-off.
3. `laravel/pao` came with the Laravel skeleton and is agent tooling. Keep or remove? `CLAUDE.md` limits which agent tools the repo uses.
4. Is branch protection on for `main` on GitHub? `CLAUDE.md` says it is protected.
5. `PRODUCT.md` targets WCAG 2.2 AA, which goes beyond the RFQ's basic accessibility (S9). Keep it?
6. `PRODUCT.md` still has two product questions under "Open, not to be guessed": the company's name on public pages and emails, and what the print views are for. These matter from WP1.

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

Planned for the coming steps, as stated at kickoff. Raise any objection before they are built:

- The admin seeder reads `GHM_ADMIN_NAME`, `GHM_ADMIN_EMAIL` and `GHM_ADMIN_PASSWORD` from `.env` and refuses to run if any is empty.
- The login lockout is keyed on a hash of the email only, never the IP. Risk: anyone who knows a staff email can lock that account for 15 minutes.
- The activity log records that an encrypted field changed, never its value.
- The admin CSP will probably need `'unsafe-eval'` and inline styles for Livewire and Alpine. Step 8 finds the minimum and records it in the technical note. The public CSP stays self-only.
- Pest runs against MySQL (`ghm_testing`) locally and in CI.
- The step 12 role users are created locally with fake names through tinker, not a committed seeder.
- The language cookie is `locale`, with values `ne` and `en`, defaulting to `ne`.
