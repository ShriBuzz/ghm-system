# Handoff

For whoever picks up the work next, on any machine, including a fresh Claude Code session that has no memory of earlier ones.

Last updated 2026-11-02. WP0, WP2 (admin side) and WP3 (alerts, reports, security) are merged. WP1 (public side) is a teammate's, in progress on its own branch.

## Where things stand

| Work | State |
|---|---|
| WP0 Foundations | Merged (pull requests #1, #2, #3) |
| WP2 step 1: reference number, PIN, attachment store | Merged (pull request #4) |
| WP2 steps 2 to 6, 8, 10: settings, case list, case page, offline entry, Playwright sign-in per role | Merged (pull request #5) |
| WP2 steps 7, 9, 11: notes, replies, documents, activity log, review, walk, traceability | Merged (pull request #6) |
| WP1 Public side | Teammate. It should use `StoreGrievanceRequest::caseRules()`, `ReferenceNumber`, `Pin` and `AttachmentStore`, and after saving a case call `$case->acknowledge($pin)` (P7, N2) |
| WP3 Alerts, reports, security | Merged (pull request #7, with three review fixes). Still open: the timed restore drill on staging, the off-server backup copy, the S8 recipients |

Step-by-step status is in the WP2 and WP3 tables in `docs/progress.md`; which requirement is where, in `docs/requirements-traceability.md`.

## Next steps

1. Keep building without NBI's answers: the clarification questions are asked after contract signing (decided 2026-11-02). The code follows the assumptions in `docs/clarification-questions.md`; its "Apply when answered" table lists the change for each answer.
2. The staging deploy (plan: 16 to 18 Nov) follows `docs/deploy.md`, rehearsed locally (WP3 step 11), once the host is known; its section 1 checks the host first, PHP 8.4.1 included. `docs/restore.md` was run end to end locally (WP3 step 10). The timed restore drill on staging (S7, X2) and the off-server backup copy follow once hosting is known (question 1); the S8 recipients at kick-off.
3. Open questions for the project lead are in `docs/progress.md` (repo visibility and the disabled branch ruleset; the slip's wording and language; Nepali copy, including the acknowledgement email; PHP 8.4 as the minimum).

## Read first

1. `CLAUDE.md`: the rules. They win over any skill.
2. `docs/rfq-requirements.md`: the RFQ requirement wording, P1 to P10, A1 to A7, N1 to N4, S1 to S10.
3. `docs/clarification-questions.md`: questions for NBI, the assumptions the code follows until they answer, and what to change after.
4. `WP0-kickoff.md`: the WP0 steps. `WP2-kickoff.md`: the WP2 (admin side) steps.
5. `docs/progress.md`: what is done, what is next, open questions and decisions.
6. `docs/local-setup.md`: how to set up a machine.
7. `docs/manual-testing.md`: how to run the app and try everything by hand. `docs/deploy.md`: putting it on a server. `docs/restore.md`: restoring a backup.
8. `PRODUCT.md`: product context for Impeccable work on public pages, emails and print views.

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

## Starting a new session

On a new machine, work through `docs/local-setup.md` first. Then start Claude Code in the project folder and give it this prompt:

> Read CLAUDE.md, docs/handoff.md and docs/progress.md. Check `git status`, the current branch and how it compares with origin/main, then run php artisan test, vendor/bin/pint --test and vendor/bin/phpstan analyse --memory-limit=1G, and report the results. Then tell me the next step from docs/handoff.md and wait for my go-ahead. Work one step at a time: a failing Pest test first, then the code, then check it in a browser with Playwright CLI using fake users you delete afterwards. Commit each step with its requirement IDs; push or open a pull request only when I ask. Bring open questions to me instead of guessing.

## Keep this current

At the end of each working session, update `docs/progress.md` (status, commits, open questions) and push, so the next machine or session starts from what is true.
