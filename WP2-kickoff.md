# WP2 Admin side: kickoff prompt for Claude Code

Paste everything below the line into Claude Code, started in the repo root.

---

Read `CLAUDE.md`, `docs/implementation-plan.md`, `docs/progress.md` and `docs/clarification-questions.md` first. You are building work package 2 (Admin side, A1 to A7 and S6) of the GHM system. WP1 (public side) is built in parallel by a teammate. Commit after each step with the requirement IDs in the message. Push and open pull or merge requests into `main`. Never push to `main`.

Two branches:

- `wp2/shared-intake`: step 1 only, merged early, because WP1 needs the same pieces.
- `wp2/admin-side`: steps 2 to 11, created from `main` after step 1 is merged. If the diff grows large, open one request after step 5 and a second for the rest.

## Before changing anything

1. Run `git status`, `git branch -a`, `php artisan test` and `vendor/bin/pint --test`. Stop and tell me if the tree is not clean or anything fails.
2. Check `docs/progress.md` for the steps already done and continue from the next open one.
3. List your assumptions and anything unclear, then wait for my go-ahead.

## Build for change

NBI has not answered the clarification questions yet. Build with the defaults below, so each answer later is data entry or a one-file edit:

1. Categories, locations, holidays and users are data the Administrator edits in settings (A7). Start them from a seeder marked as placeholder.
2. Every other list NBI may change (channel, recurrence, contact method, the five demographic fields) is one PHP enum with labels in `lang/en.json` and `lang/ne.json`. No admin-editable option tables.
3. Columns store codes (`drop_box`), never labels. Columns stay plain strings with the enum cast in PHP, so adding an option needs no migration.
4. One field list: offline entry reuses the public form's validation rules (a shared FormRequest), one export column list, one case-page layout.
5. Dates are stored in AD. A Bikram Sambat input, if NBI wants it, is only an input widget.
6. Each default you use goes into the "Apply when answered" table in `docs/clarification-questions.md`, with the file to change.
7. Do not build what waits on NBI and is costly to undo: the Bikram Sambat picker, retention cleanup, Administrator alert opt-in, reply emails to complainants.

Defaults until NBI answers: the 15-working-day clock runs while the status is Received, Under assessment, Under investigation or Reopened, and stops at Resolved, Referred / Out of scope or Closed. `closed_at` is set at Closed and cleared at Reopened. Channels: `web`, `phone`, `email`, `drop_box`, `in_person`. Saturday is the only weekend day. The staff panel is in English (question 14).

## Steps

1. **Shared intake pieces (P7, S4, S5), branch `wp2/shared-intake`.** `app/Support/ReferenceNumber.php`: `GHM-YYYY-NNNN` from a row-locked `reference_counters` row, inside the caller's transaction, restarting each year. `app/Support/Pin.php`: a random PIN of `ghm.pin_length` digits; the caller stores only its hash. `app/Support/AttachmentStore.php`: checks the real MIME type (JPEG, PNG, WebP, PDF) and `ghm.upload_max_kb`, re-encodes images with GD to strip EXIF, stores under a random name on the private disk, creates the `Attachment` row. Tests: format, yearly restart, unique numbers, PIN never stored in clear, oversize and renamed `.exe` rejected, a JPEG with GPS stored without EXIF, nothing under `public/`. Open the request and tell me, so the teammate on WP1 can build on it.
2. **Panel housekeeping (A1).** Staff panel in English; remove Filament's info widget from the dashboard. Record the language decision in `docs/progress.md` and against question 14.
3. **Working-day count (A2).** `working_days_open` column on `grievances`; `app/Support/WorkingDays.php` skipping `ghm.weekend_days` and the `holidays` table in Nepal time; the observer recounts on a status change and sets or clears `closed_at`; a `ghm:recount-working-days` command scheduled daily at 00:30 for open cases.
4. **Settings (A7, A5).** Filament resources for categories (with the restricted flag), locations, holidays and users, Administrator only through policies. No delete; deactivate with `is_active`. An Administrator cannot deactivate themselves or change their own role. A placeholder seeder for categories and locations. Log a category's restriction change.
5. **Case list (A2, A5).** `GrievanceResource` whose `getEloquentQuery()` is `Grievance::visibleTo(auth()->user())`. Columns: reference, received, category, location, status, priority, assignee, days open with a red badge past `ghm.sla_days`. Search by reference only. Filters: status, category, location, received date range, "Overdue only". A focal person opening a restricted case's address gets the denied page.
6. **Case page (A3, A4, A5).** The submission (decrypted fields; anonymous shows no contact block). Actions authorised by `update`: status, priority, assign (only active users who can see the case, from one method built on `visibleTo()`), mark restricted (warning for a focal person), remove restriction (Administrator only). A case that becomes restricted drops an assignee who can no longer see it.
7. **Notes, replies and documents (A3, S4).** Notes: create only, dated, with author, no edit or delete. Replies stored with direction `out`; complainant messages read-only. Staff uploads through `AttachmentStore`. A download route that authorises `view` on the case and streams from the private disk with `nosniff`.
8. **Offline entry (A6).** `app/Enums/Channel.php`; a create page with a required channel (not `web`), received date, the public form's fields and rules, `created_by`; reference and PIN in one transaction; a confirmation with a printable slip showing them once, so a focal person entering a restricted case can still hand them over.
9. **Activity log (S6).** spatie's `activity()` directly: case created, viewed, status, priority, assigned, restriction, note, reply, attachment uploaded and viewed, category restriction, login, failed login. Properties hold enum values and IDs only, never encrypted text, emails or IPs. A read-only, Administrator-only log page.
10. **Sign-in per role in Playwright (A1).** Seeded authenticator secrets for test users, so specs sign in with MFA unattended; each role sees only its menu. This also unblocks the S2 idle and S10 enrolment specs.
11. **Close WP2.** Playwright CLI walks with `-s=admin`, `-s=focal`, `-s=restricted` at 360 px and desktop; `/ponytail-review` with every must-fix item fixed; A1 to A7 and S6 rows in `docs/requirements-traceability.md`; `docs/progress.md`.

For a proof of concept to demo early, do steps 1, 3, 4, 5, 6 and 8 first; offline entry creates the demo cases.

Each step: a Pest test grouped by its ID, a Playwright spec tagged with it for anything a user sees, then mark the step in `docs/progress.md` with its commit.

## Done when

- `php artisan test`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` and `npx playwright test` pass locally and in CI.
- No staff screen, file route or action reaches a case except through `Grievance::visibleTo()` and `GrievancePolicy`.
- A focal person never sees a restricted case in the list, by address, by file, or as an assignee option.
- `/ponytail-review` on the branch has no must-fix items.
- Each request into `main` lists the requirement IDs touched, anything skipped, and any risk I should know.
