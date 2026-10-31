# Technical note

Started in WP0. Every Composer or npm package gets a one-line reason here when it is added.

## Platform

| Part | Version | Notes |
|---|---|---|
| PHP | 8.4 (8.3+ required) | Extensions: curl, fileinfo, intl, mbstring, openssl, pdo_mysql, sodium, zip |
| Laravel | 13.39 | From the `laravel/laravel` 13.x skeleton, with Vite and Tailwind removed |
| Database | MySQL 8 or MariaDB 10.6+ | |
| Node | 20+ | Dev and CI only (Playwright, Impeccable); nothing on the server needs Node |

## Composer packages

| Package | Kind | Why |
|---|---|---|
| `laravel/framework` | runtime | The application framework. |
| `laravel/tinker` | runtime | Skeleton default; console REPL for local debugging. |
| `fakerphp/faker` | dev | Fake data for factories and tests; real grievance data is never used outside production. |
| `laravel/pail` | dev | Skeleton default; tails the local log. |
| `laravel/pao` | dev | Skeleton default; condenses test and Pint output when an AI agent runs them. Under review, see below. |
| `laravel/pint` | dev | Code style check (`vendor/bin/pint --test`). |
| `mockery/mockery` | dev | Test doubles. |
| `nunomaduro/collision` | dev | Readable console errors in tests. |
| `phpunit/phpunit` | dev | Test runner underneath Pest. |

## npm packages (dev and CI only)

| Package | Why |
|---|---|
| `@playwright/test` | Browser specs tagged by requirement ID (`@P7`), run locally and in CI. |
| `@playwright/cli` | Named browser sessions per role for walking user flows. |
| `@axe-core/playwright` | Accessibility checks inside Playwright specs. |

## Agent tooling (not shipped)

- Impeccable 4.8.0, installed with `npx impeccable install --providers=claude --scope=project`. Skills and agents live in `.claude/`. The design hook sits in `.claude/settings.local.json`, which is per machine and not committed. The 20 MB engine binary is not committed either; the launcher downloads it on demand and checks it against `engine-checksums.txt`.
- `.impeccable/config.json` sets `"telemetry": false` for everyone. Impeccable still calls impeccable.style when it starts a new design, because that call is the feature; this happens on developer machines only, never from the application.
- Playwright CLI skill installed with `npx playwright cli install --skills` into `.claude/skills/playwright-cli`.

## Open points

- `laravel/pao` is agent tooling that came with the skeleton. `CLAUDE.md` limits which agent tools the repo uses, so keep or remove it is a decision for the project lead.
