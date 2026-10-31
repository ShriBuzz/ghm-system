# Handoff

For whoever picks up the work next, on any machine, including a fresh Claude Code session that has no memory of earlier ones.

Last updated 2026-10-31, after WP0 step 2, when work moved from the Windows laptop to a Mac.

## Read first

1. `CLAUDE.md`: the rules. They win over any skill.
2. `WP0-kickoff.md`: the WP0 steps.
3. `docs/progress.md`: what is done, what is next, open questions and decisions.
4. `docs/local-setup.md`: how to set up a machine.
5. `PRODUCT.md`: product context for Impeccable work on public pages, emails and print views.

## What git does not carry

| Item | Why it is not in git | On the new machine |
|---|---|---|
| `.env` | Holds secrets | Copy `.env.example`, run `php artisan key:generate`, fill in the database settings |
| `vendor/`, `node_modules/` | Installed dependencies | `composer install`, `npm ci` |
| Playwright browsers | Installed per machine | `npx playwright install chromium` |
| Local databases | Per machine, fake data only | Create `ghm` and `ghm_testing` as in `docs/local-setup.md` |
| Impeccable hook and engine | Per machine by design | `npx impeccable@4.8.0 install --providers=claude --scope=project -y` |
| Ponytail plugin files | Claude Code installs plugins per machine | Accept Claude Code's offer, or `/plugin install ponytail@ponytail` |
| Claude Code chat history and memory | Kept in the old machine's home folder | This file and `docs/progress.md` carry the context |

A fresh `APP_KEY` is fine now because no encrypted data exists yet. Once it does, a database copied between machines only decrypts with the same `APP_KEY`. Local data is fake, so recreate it rather than copying it.

## First session on the Mac

1. Work through `docs/local-setup.md`: tools, MySQL, GitHub, the project, agent tooling.
2. Start Claude Code in the project folder and give it this prompt:

   > Read CLAUDE.md, WP0-kickoff.md, docs/progress.md and docs/handoff.md. We are on branch wp0/foundations and kickoff steps 1 and 2 are done. Check this machine against docs/local-setup.md (php -v, the PHP extensions, composer -V, node -v, MySQL reachable as the ghm user, php artisan test, vendor/bin/pint --test) and report anything missing. Then continue with step 3. Commit and push after each step, and update docs/progress.md as you go. Stop before step 6 if docs/implementation-plan.md is still missing, and bring open questions to me instead of guessing.

## Keep this current

At the end of each working session, update `docs/progress.md` (status, commits, open questions) and push, so the next machine or session starts from what is true.
