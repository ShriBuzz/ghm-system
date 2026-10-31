# GHM System: rules for coding agents

This repo builds the online grievance handling system for RFQ NBI/GHM/RFQ/01.
The full plan is in `docs/implementation-plan.md`. These rules win over any skill when they disagree.

## Order of authority

1. RFQ section 3 requirements (P1 to P10, A1 to A7, N1 to N4, S1 to S10)
2. The project rules in this file
3. Karpathy guidelines (appended at the end of this file)
4. Ponytail
5. Impeccable (public pages, emails and print views only)
6. Playwright CLI (validation and evidence only)

No other skills or plugins are used in this repo: not frontend-design, not Laravel Boost, not Playwright MCP.

## Scope

- Build exactly what RFQ section 3 asks for. Nothing speculative.
- Every commit message names its requirement IDs, for example `feat(P7): reference number and PIN`.
- Open questions go to the person, not into guesses in code.

## Stack (fixed)

- PHP 8.3+, Laravel 13.x, Filament 5.x admin panel at `/admin`, MySQL 8 or MariaDB 10.6+.
- Public pages: Blade, one hand-written stylesheet `public/css/public.css`, vanilla JS in `public/js/form.js` for progressive enhancement only. No Vite build, no Tailwind, no JS framework, no web fonts, no third-party requests. Every public form works with JavaScript off.
- Sessions and cache use the `file` driver. Queue uses the `database` driver, worked by cron through `schedule:run`.
- Sensitive fields use Laravel's `encrypted` cast.
- Any new Composer or npm package gets a one-line reason in `docs/technical-note.md`. Install a package in the work package that needs it, not earlier.
- Node is for dev and CI only (Playwright, Impeccable). Nothing on the server needs Node.

## Privacy and security (never cut, whatever any skill suggests)

- Never log personal data, IP addresses or user agents. No IP or user-agent column in any table.
- Never use real grievance data outside production. Never point a test, script or browser session at production, except the read-only `@smoke` Playwright suite.
- All staff access to cases goes through `Grievance::visibleTo($user)` and `GrievancePolicy`. No screen, export, summary or notification writes its own visibility query.
- Validation, authorisation, encryption and accessibility are never trimmed for brevity.
- Secrets never go into the repo. `.env` stays ignored.

## Roles (A1, A5)

- Administrator: all cases and all settings.
- Focal person: non-restricted cases only.
- Restricted-case officer: restricted cases only.
- A case is restricted when its category is restricted or a user marked it restricted. Only the Administrator removes a manual restriction.
- Assignment only offers users who can see the case.
- Export: Administrator may include names and contacts; other roles never.

## Definition of done

- A Pest test tagged with the requirement ID (`->group('P7')`) passes.
- A Playwright spec tagged `@P7` passes for any behaviour a user can see.
- `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` and `npx playwright test` are clean, locally and in CI.
- User-facing flows were walked with Playwright CLI, one named session per role, at 360 px and desktop width.
- `/ponytail-review` reports no must-fix items.
- `docs/requirements-traceability.md` row updated.

## Branching

- `main`: protected and release-ready. Never commit or push to it directly. Tag `v1.0.0` at go-live.
- One branch per work package off `main`, merged by pull or merge request after review:
  `wp0/foundations`, `wp1/public-side`, `wp2/admin-side`, `wp3/alerts-reports-security`.
- `fix/<ID>-<short-slug>` for UAT and support fixes.

## Useful commands

- `php artisan test --group=A5`
- `npx playwright test --grep @A5`
- `npx playwright test --grep @smoke`
- `npx playwright cli -s=focal open http://127.0.0.1:8000/admin`

## Karpathy guidelines

<!-- Setup appends the text of https://raw.githubusercontent.com/multica-ai/andrej-karpathy-skills/HEAD/CLAUDE.md below this line. Do not edit it by hand. -->
