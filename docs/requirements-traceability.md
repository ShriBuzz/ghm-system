# Requirements traceability

One row per RFQ requirement (wording in `docs/rfq-requirements.md`) plus the plan's implied rows X1 and X2: where it lives in the code, which tests prove it, and its status. Last updated 2026-11-01, during WP2 (steps 2 to 6, 8 and 10 built).

Every Pest test is grouped by its ID and every Playwright spec tagged with it, so one requirement can be checked on its own:

```sh
php artisan test --group=A5
npx playwright test --grep @A5
```

Status counts the whole requirement, not the part one work package built:

- **Not started**: nothing built yet; schema columns alone do not count.
- **In progress**: part is built and tested, the rest is named in the notes.
- **Tested**: everything the RFQ asks is built, with passing Pest (and Playwright, where a browser can see it) checks.
- **UAT passed**: signed off by NBI or the company. Nothing is at this stage before the 18 Nov test version.

## Public side

| ID | Requirement | M/D | Status | Work package | Code | Tests | Notes |
|---|---|---|---|---|---|---|---|
| P1 | Mobile-first form from the company website, no account | M | In progress | WP1 | `resources/views/layouts/public.blade.php`, `public/css/public.css` | Playwright: `/ne` and `/en` fit 360 px | The public shell is mobile-first and needs no account. The form is WP1. |
| P2 | Nepali and English with a language switch | M | Tested | WP0 | `App\Http\Middleware\SetLocale`, `routes/web.php`, the public layout, `lang/en.json`, `lang/ne.json` | Pest `PublicShellTest`; Playwright: switch both ways, with JavaScript off, remembered language | Each language has its own address. Every new page and email adds its text to both files. Manual check outstanding: a Nepali speaker reads every page. |
| P3 | Notice on confidentiality, no retaliation, next steps | M | Not started | WP1 | | | Wording comes from NBI and is kept as an editable setting (clarification question 2). |
| P4 | Anonymous or named, with contact details and preferred method | M | In progress | WP2 step 8, WP1 | `StoreGrievanceRequest::caseRules()` (shared by offline entry and the WP1 form), `App\Enums\ContactMethod` | Pest `OfflineEntryTest` | Offline entry enforces it: an anonymous case stores no contact details; a named one needs a name and the phone or email its preferred method names. The public form is WP1. Contact methods and anonymous-with-email wait on NBI (questions 6 and 9). |
| P5 | Category, location, date, frequency, description, one attachment up to 5 MB | M | Not started | WP1 | `grievances`, `attachments`, `config/ghm.php` (`upload_max_kb`) | | The Bikram Sambat date input and the "don't remember the exact date" flag come with the form (question 8). |
| P6 | Optional demographic fields, each with "prefer not to say" | M | Not started | WP1 | Five encrypted columns on `grievances` | Storage covered by `EncryptionTest` (S3) | Option lists wait on NBI (question 5). |
| P7 | Reference number and random PIN, emailed if an email was given | M | In progress | WP2 steps 1 and 8, WP1 | `App\Support\ReferenceNumber`, `App\Support\Pin`, `reference_counters`, `grievances.pin_hash`, `config/ghm.php` (`pin_length`), `RecordedCase` (offline slip), `config/session.php` (`encrypt`) | Pest `ReferenceNumberTest`, `OfflineEntryTest`; Playwright: offline slip shows the reference and PIN once | Offline entry shows both once on the slip and stores only the PIN's hash; the session that carries the PIN to the slip is always encrypted. Showing them after a public submission, with the advice to note them, and the email are WP1. |
| P8 | Status page by reference and PIN, with replies and complainant messages | M | Not started | WP1 | `grievance_messages` | | |
| P9 | Spam protection | M | Not started | WP1 | | | |
| P10 | QR code to the form for posters | D | Not started | WP1 | | | A poster can point at `/ne` or `/en` directly. |

## Admin side

| ID | Requirement | M/D | Status | Work package | Code | Tests | Notes |
|---|---|---|---|---|---|---|---|
| A1 | Secure login with three roles | M | Tested | WP0, WP2 | `App\Enums\Role`, `App\Models\User`, `App\Providers\Filament\AdminPanelProvider`, `Database\Seeders\AdministratorSeeder`, `Grievance::visibleTo()`, `App\Policies\GrievancePolicy`, `ghm:e2e-users` (`routes/console.php`, local and testing only) | Pest `AdminPanelTest`, `AdministratorSeederTest`, `CaseAccessTest`, `E2eUsersTest`; Playwright: staff sign-in reachable and locked down, each role signs in with password and authenticator code, each role's menu | Sign-in, roles, required MFA, deactivated users, case visibility and each role's menu are tested. The step 11 browser walk per role is still to do. |
| A2 | Case list with search, filters, days open, highlight past 15 working days | M | In progress | WP2 steps 3 and 5 | `GrievanceResource`, `App\Support\WorkingDays`, `GrievanceObserver`, `ghm:recount-working-days`, `config/ghm.php` (`sla_days`, `weekend_days`), `holidays` | Pest `CaseListTest`, `WorkingDaysTest`; Playwright: search by reference (in the case specs) | Built and tested in Pest. Playwright checks for the filters and the overdue badge are still to write. Weekend and holiday list wait on NBI (question 10). |
| A3 | Case page: status, priority, assignment, internal notes, replies, uploads | M | In progress | WP2 steps 6 and 7 | `ViewGrievance`, `Grievance::assignableUsers()`, `Grievance::dropUnseeingAssignee()`, `GrievancePolicy::update()` | Pest `CasePageTest`; Playwright: status, priority and assignment offered only to staff who can see the case | Submission, status, priority and assignment are built and tested. Notes, replies and uploads are WP2 step 7. |
| A4 | Statuses and priorities exactly as listed | M | Tested | WP0, WP2 step 6 | `App\Enums\Status`, `App\Enums\Priority`, `lang/en.json`, `lang/ne.json` | Pest `CaseValuesTest`, `CasePageTest`; Playwright: status and priority set on the case page | Nepali labels pending kick-off confirmation (question 11). |
| A5 | Restricted cases visible only to restricted-case officers and the Administrator | M | In progress | WP0, WP2, WP3 | `Grievance::visibleTo()`, `GrievancePolicy`, `User::canSee()`, `App\Observers\GrievanceObserver`, `Category::booted()`, `User::booted()` | Pest `CaseAccessTest`, `RestrictionTest`, `CaseListTest`, `CasePageTest`; Playwright: a focal person enters a restricted case, gets the slip, then cannot find or open it; settings refused to non-administrators | List, search, record addresses, assignment and restriction are built and tested. Files (WP2 step 7) and summary, export and alerts (WP3) follow. |
| A6 | Offline entry with a channel field | M | Tested | WP2 step 8 | `App\Enums\Channel`, `CreateGrievance`, `GrievanceForm`, `RecordedCase`, `resources/views/filament/slip-print.blade.php` | Pest `OfflineEntryTest`, `CaseAccessTest`; Playwright: offline entry with the slip shown once, the printed slip, a restricted case entered by a focal person | Channel list waits on NBI (question 7). The printed slip's wording and language for the complainant are an open question (`docs/progress.md`). |
| A7 | Settings: users, categories with restricted flag, locations | M | In progress | WP2 step 4 | Settings resources (`CategoryResource`, `LocationResource`, `HolidayResource`, `UserResource`), `SettingsPolicy`, `HolidayPolicy`, `PlaceholderListsSeeder` | Pest `SettingsTest`, `AdminPanelTest`; Playwright: only the Administrator has the Settings menu, the other roles are refused the pages | Built and tested in Pest. A Playwright check of the Administrator editing each list is still to write. Logging a category's restriction change is S6 (WP2 step 9). |

## Notifications and reports

| ID | Requirement | M/D | Status | Work package | Code | Tests | Notes |
|---|---|---|---|---|---|---|---|
| N1 | Detail-free alerts; restricted cases alert only restricted-case officers | M | Not started | WP3 | | | Recipients come from `visibleTo()`. Administrator alerts wait on NBI (question 13). |
| N2 | Acknowledgement email to the complainant | M | Not started | WP3 | | | Mail transport waits on NBI (question 3). |
| N3 | Summary by status and category for a date range | M | Not started | WP3 | | | |
| N4 | Excel and CSV export; names and contacts only if the Administrator opts in | M | Not started | WP3 | | | Who may export waits on NBI (question 12). |

## Security and data

| ID | Requirement | M/D | Status | Work package | Code | Tests | Notes |
|---|---|---|---|---|---|---|---|
| S1 | HTTPS on all pages | M | Tested | WP0 | `App\Http\Middleware\SecurityHeaders`, `bootstrap/app.php` | Pest `SecurityHeadersTest`; Playwright `@smoke` specs | The certificate and an SSL Labs grade are checked on staging. Behind a TLS-ending proxy, trusted proxies must be set first (`docs/technical-note.md`). |
| S2 | Hashed passwords; 15-minute lock after 5 failed logins; logout after 30 idle minutes | M | Tested | WP0 | `App\Filament\Pages\Auth\Login`, `App\Filament\Pages\Auth\EditProfile`, `public/js/admin-idle.js`, `SESSION_LIFETIME=30` (also the default in `config/session.php`), the `hashed` cast on `User` | Pest `LoginHardeningTest`, `ProfileTest`, `AdministratorSeederTest`; Playwright: lock message on the sixth failed sign-in | The idle reload was walked in a browser with a 1-minute lifetime. An automated Playwright idle spec needs a signed-in user with a seeded authenticator secret. |
| S3 | Sensitive fields encrypted with the framework's encryption | M | Tested | WP0 | `encrypted` casts on `Grievance`, `GrievanceMessage`, `GrievanceNote`, `Attachment`, and the MFA secret on `User` | Pest `EncryptionTest`, `MultiFactorAuthenticationTest` | Keeping `APP_KEY` recoverable is X2. |
| S4 | Files outside the public folder, viewable only with case access | M | In progress | WP2 steps 1 and 7 | `App\Support\AttachmentStore`, `config/filesystems.php` (`serve` off) | Pest `AttachmentStoreTest` | Private storage under random names and the type and size check are tested. The case-checked download route is WP2 step 7. |
| S5 | No IP address or device data stored | M | In progress | WP0, WP1 | Migrations with no IP or user-agent column, the `file` session driver, sign-in throttles keyed on an email hash | Pest `PrivacySchemaTest`, `LoginHardeningTest` and `ProfileTest` (no IP in throttles) | Photo metadata stripping is built (`AttachmentStore`, Pest `AttachmentStoreTest`). IP-free public rate limits and log context come in WP1. Host access logs wait on NBI (question 4). |
| S6 | Activity log of status changes, notes, views and exports | M | In progress | WP2 | `activity_log` table (`spatie/laravel-activitylog`) | | Only the table exists. Events, and the rule that encrypted values are never logged, come in WP2. |
| S7 | Daily backup kept 14 days, with restore instructions | M | Not started | WP3 | | | |
| S8 | Vendor confidentiality and 24-hour breach reporting | M | Not started | WP3 | | Not testable in code | Breach template and production-access log are delivered with the handover pack. |
| S9 | Basic accessibility on public pages | M | In progress | WP0, WP1 | The public layout, `public/css/public.css` | Pest `PublicShellTest`; Playwright: axe WCAG 2.2 AA in both languages at 360 px, language switch with JavaScript off | Target is WCAG 2.2 AA (confirmed 2026-10-31). Form labels and the error summary come with the WP1 form. Manual checks with TalkBack and NVDA are outstanding. |
| S10 | Two-factor login for admin users | D | Tested | WP0 | `AdminPanelProvider` (required authenticator-app MFA with recovery codes), `User` | Pest `MultiFactorAuthenticationTest` | Users enrol on the forced setup page at first sign-in and manage MFA and recovery codes on `/admin/profile`. The plan's Playwright enrolment check needs a seeded authenticator secret. |

## Implied by the RFQ (plan section 9)

| ID | Requirement | Status | Work package | Code | Tests | Notes |
|---|---|---|---|---|---|---|
| X1 | Light public pages with no third-party requests | In progress | WP0, WP1 | `public/css/public.css` (2.5 KB), no scripts on public pages | Playwright: public pages load nothing from other sites | The under-50 KB-per-page check comes with the form. |
| X2 | Encryption key recoverable | Not started | WP3 | | | Proven by the restore drill on staging. |
