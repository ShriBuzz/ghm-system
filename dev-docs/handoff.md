# Handoff

For whoever picks up the work next, on any machine, including a fresh Claude Code session that has no memory of earlier ones.

Last updated 2026-11-12: the company's answers and the brief of its Policy and SOP updates arrived, and are planned as WP4 in `dev-docs/answers-change-plan.md`. Steps 1 (the company's lists and labels, pull request #19), 2 (the count restarting on reopening and holidays entered by focal persons, pull request #20) 3 (Bikram Sambat dates on the Nepali form) and 10 (Bikram Sambat dates and Nepali names on the staff screens) are built. Before that (2026-11-03): WP0, WP2 (admin side) and WP3 (alerts, reports, security) were built, and the whole repo had been through `/ponytail-audit` with every finding fixed. WP1 (public side) is built and reviewed: all nine steps, in `main` once pull request #14 merges.

## Where things stand

| Work | State |
|---|---|
| WP0 Foundations | Merged (pull requests #1, #2, #3) |
| WP2 step 1: reference number, PIN, attachment store | Merged (pull request #4) |
| WP2 steps 2 to 6, 8, 10: settings, case list, case page, offline entry, Playwright sign-in per role | Merged (pull request #5) |
| WP2 steps 7, 9, 11: notes, replies, documents, activity log, review, walk, traceability | Merged (pull request #6) |
| WP1 Public side | Steps 1 to 5 (form, reference and PIN page, spam protection) merged in pull request #11. Steps 6 and 7 (status page, #12) and 8 and 9 (QR poster, Impeccable critique and audit fixes, walks, `DESIGN.md`, #13) were merged into their stacked branches, not `main`; #14 from `wp1/land-steps-6-9` brings them to `main`. It uses `StoreGrievanceRequest::caseRules()`, `ReferenceNumber`, `Pin` and `AttachmentStore`, and after saving a case call `$case->acknowledge($pin)` (P7, N2) |
| WP3 Alerts, reports, security | Merged (pull requests #7 to #9), then the `/ponytail-audit` fixes (WP3 step 12, branch `wp3/audit-fixes`). Still open: the timed restore drill on staging, the off-server backup copy, the S8 recipients |
| Handover package docs | Branch `handover/finalize` (2026-11-13), stacked on `fix/S7-mysqldump-tablespaces`, not pushed: the guides checked against the code and walked, the user guide at 5 A4 pages with new pictures, the technical note at one page, docs that start from the release zip, a new troubleshooting guide, an Administrator's guide and the Nepali user guide draft. What is left is under "Handover package" in `dev-docs/progress.md` |
| WP4 The company's answers and Policy updates | Planned 2026-11-12. Steps 1 to 5, 7 and 10 to 12 built (pull requests #19 to #28 merged), and a review of the whole of WP4 fixed on `wp4/review-fixes` (step 17): every step of the answers track is in; the next is the close-out (16), after the call with NEEK on 13 Nov: re-take the user-guide pictures, a final walk per role, review, traceability, and the sync to `main`. Decided the same day: answers first. The answers to our clarification questions, and what the company added while answering them, are built now, with the brief's public page text (about 6 working days); the new requirements are held until scope and price are agreed. New IDs U1 to U16, follow-up questions C1 to C27 |

Step-by-step status is in the WP2 and WP3 tables in `dev-docs/progress.md`; which requirement is where, in `docs/requirements-traceability.md`.

## Next steps

1. Merge #14. Stacked pull requests: GitHub moves the next one onto `main` only when the merged branch is deleted, so retarget it by hand (`gh pr edit <number> --base main`) before merging it. Then, without NBI: the staff-panel walk per role that A1 still lists, the critique's P3 polish items, and the manual checks (a low-cost Android phone on 3G; TalkBack and NVDA). The WP1 table in `dev-docs/progress.md` has each step's decisions; `DESIGN.md` has the public pages' visual rules, and `.impeccable/critique/` the P3 polish items left. Playwright runs share this machine's file cache with `php artisan serve`, so after a full run the local hourly limits for 127.0.0.1 are used up: start a walk server with `GHM_SUBMISSIONS_PER_HOUR=100 GHM_FAILED_LOOKUPS_PER_HOUR=100`.
2. WP4. The company answered most clarification questions on 2026-11-12 and sent the updates from its Policy and SOP. `dev-docs/answers-change-plan.md` sets out what each answer changes and how big the change is, with the new IDs U1 to U16, the follow-up questions C1 to C27 and the answers-first plan. Before building:
   - decided 2026-11-12: answers and the company's additions first, starting with step 1 on `wp4/answers-lists` (the WP4 table in `dev-docs/progress.md`); the new requirements stay held until scope and price are agreed (open question 7);
   - the follow-up questions go to the company: `dev-docs/neek-call-2026-11-13.md` is the brief for the call with NEEK on 13 Nov (what is built, the new requirements held for a separately priced phase, the defaults to confirm, and every open question by urgency);
   - the three PDFs and the two Word files in `docs/` stay untracked: the repository is public, they are the company's internal documents, and the brief names staff (open question 9).

   Until a step is built, the code follows the assumptions in `dev-docs/clarification-questions.md`. The requirement wording for U1 to U16 is at the end of `docs/rfq-requirements.md`.
3. The staging deploy (plan: 16 to 18 Nov) follows `docs/deploy.md`, rehearsed locally (WP3 step 11), once the host is known; its section 1 checks the host first, PHP 8.4.1 included. `docs/restore.md` was run end to end locally (WP3 step 10). The timed restore drill on staging (S7, X2) and the off-server backup copy follow once hosting is known (question 1); the S8 recipients at kick-off.
4. Open questions for the project lead are in `dev-docs/progress.md` (repo visibility and the disabled branch ruleset; the slip's wording and language; Nepali copy, including the acknowledgement email).

## Read first

1. `CLAUDE.md`: the rules. They win over any skill.
2. `docs/rfq-requirements.md`: the RFQ requirement wording, P1 to P10, A1 to A7, N1 to N4, S1 to S10, and the company's updates U1 to U16 at the end.
3. `dev-docs/clarification-questions.md`: questions for NBI, the assumptions the code follows until they answer, and what to change after. `dev-docs/answers-change-plan.md`: the company's answers of 2026-11-12, what each changes, and the WP4 plan.
4. `dev-docs/progress.md`: what is done, what is next, open questions and decisions.
5. `docs/local-setup.md`: how to set up a machine.
6. `docs/manual-testing.md`: how to run the app and try everything by hand. `docs/deploy.md`: putting it on a server. `docs/restore.md`: restoring a backup.
7. `PRODUCT.md`: product context for Impeccable work on public pages, emails and print views. `DESIGN.md`: the visual rules those pages follow.

## Branches and sync

`main-backup` is the working branch and holds everything. `main` is what the client gets: the same code without the dev-only files. Docs follow the same split: `docs/` is the handover and ships, `dev-docs/` is internal and never does. The rules are in `CLAUDE.md`, Branching. The full snapshot before the split is commit `8d8c0a4`.

To bring work from `main-backup` into `main`:

1. On a clean tree, run `scripts/sync-main.sh`. It makes a `sync/<date-time>` branch from `origin/main`, merges `origin/main-backup` into it, removes the dev-only files and checks that no shared file mentions them.
2. `git push -u origin HEAD`, then `gh pr create --base main`.
3. Merge on GitHub with "Create a merge commit". A squash or rebase cuts `main` off from `main-backup`'s history, and the next sync then conflicts on every shared file.

The handover zip for the client: `git archive --format=zip -o ghm-system.zip main`.

## What git does not carry

| Item | Why it is not in git | On the new machine |
|---|---|---|
| `.env` | Holds secrets | Copy `.env.example`, run `php artisan key:generate`, fill in the database settings |
| `vendor/`, `node_modules/` | Installed dependencies | `composer install`, `npm ci` |
| Playwright browsers | Installed per machine | `npx playwright install chromium` |
| Local databases | Per machine, fake data only | Create `ghm` and `ghm_testing` as in `docs/local-setup.md`. On a machine set up before WP4 step 1, refresh both once, because the seeder fills empty lists only and leaves the old placeholder categories in place: `php artisan test` rebuilds `ghm_testing` (or `DB_DATABASE=ghm_testing php artisan migrate:fresh`), and `php artisan migrate:fresh --seed` rebuilds `ghm` |
| Impeccable hook and engine | Per machine by design | `npx impeccable@4.8.0 install --providers=claude --scope=project -y` |
| Ponytail plugin files | Claude Code installs plugins per machine | Accept Claude Code's offer, or `/plugin install ponytail@ponytail` |
| Claude Code chat history and memory | Kept in the old machine's home folder | This file and `dev-docs/progress.md` carry the context |

## Agent tooling (once per machine, not shipped)

| Tool | Version | Used for |
|---|---|---|
| Claude Code | current | The coding agent, with the Ponytail plugin |

- **Claude Code and Ponytail.** Start Claude Code in the project folder and trust the folder. `.claude/settings.json` names the Ponytail marketplace and enables the plugin, so Claude Code offers to install it. If it does not, run `/plugin marketplace add DietrichGebert/ponytail` and `/plugin install ponytail@ponytail` at project scope, then restart Claude Code.
- **Impeccable design hook.** Impeccable 4.8.0; its skills and agents live in `.claude/`. The hook is kept out of git on purpose. Re-create it with the same version:

  ```sh
  npx impeccable@4.8.0 install --providers=claude --scope=project -y
  git status   # expect no changes; the hook lands in the ignored .claude/settings.local.json
  ```

  The macOS engine binary (20 MB, not committed) downloads on first use and is checked against `engine-checksums.txt`. If `git status` shows changed skill files, look at the diff before committing anything. If the installer adds its `impeccable-ignore` block to the root `.gitignore` again, delete that block: its rules live in `.impeccable/.gitignore`, and the root `.gitignore` is shared with `main`.
- **`/impeccable init`** is already done (`PRODUCT.md`). Do not run it again.
- `.impeccable/config.json` sets `"telemetry": false` for everyone. Impeccable still calls impeccable.style when it starts a new design, because that call is the feature; this happens on developer machines only, never from the application.
- **Playwright CLI skill**, installed with `npx playwright cli install --skills` into `.claude/skills/playwright-cli`.

A fresh `APP_KEY` is fine now because no encrypted data exists yet. Once it does, a database copied between machines only decrypts with the same `APP_KEY`. Local data is fake, so recreate it rather than copying it.

## Starting a new session

On a new machine, work through `docs/local-setup.md` first. Then start Claude Code in the project folder and give it this prompt:

> Read CLAUDE.md, dev-docs/handoff.md and dev-docs/progress.md. Check `git status`, the current branch and how it compares with origin/main-backup, then run php artisan test, vendor/bin/pint --test and vendor/bin/phpstan analyse --memory-limit=1G, and report the results. Then tell me the next step from dev-docs/handoff.md and wait for my go-ahead. Work one step at a time: a failing Pest test first, then the code, then check it in a browser with Playwright CLI using fake users you delete afterwards. Commit each step with its requirement IDs; push or open a pull request only when I ask. Bring open questions to me instead of guessing.

## Keep this current

At the end of each working session, update `dev-docs/progress.md` (status, commits, open questions) and push, so the next machine or session starts from what is true.
