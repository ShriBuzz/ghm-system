# Clarification questions for NBI

Questions for NBI, and what to change in the repo once NBI answers. They gather plan section 11's questions and the ones found while building.

They were planned for the RFQ clarification email, due 2 Nov 2026 (RFQ section 1). Decided 2026-11-02: they are asked after contract signing instead. Until NBI answers, the code follows the assumptions in "Apply when answered" below, and the work continues on what does not depend on them.

| Email | Status |
|---|---|
| Questions to NBI | Asked after contract signing, by WhatsApp |
| NBI's answers | Received 2026-11-12 for questions 2, 5 to 8 and 10 to 15, with the company's brief of its Policy and SOP updates. Questions 1, 3, 4, 9 and 16 are still open |

The answers, what each changes in the system, the conflicts between the company's documents and the follow-up questions (C1 to C27) are in `docs/answers-change-plan.md`. Section 1 there follows the WhatsApp answers one by one; in the "Answer" column below, "Plan 1.4" means its section 1.4. The changes are planned as WP4 and are not applied yet, so every "Done" box below stays empty until its WP4 step is built.

The email text below was written to go before the quotation; adjust its opening and question 1 (which mentions quoting new hosting) when it is sent.

Questions 1, 3, 4, 5, 7, 8, 10 to 14 and 16 are written up for the first client meeting, with the options in use today, in `docs/client-discussion.md`.

When the answers arrive: record each one in the "Answer" column below, apply the change listed for it, tick it off, and move any decision into `docs/progress.md`.

## Email text

Subject: Clarification questions: RFQ NBI/GHM/RFQ/01 – [your company name]

> Dear NBI team,
>
> Thank you for the RFQ for the online grievance handling system. Before we submit our quotation, we would be grateful for answers to the questions below. Requirement IDs refer to RFQ section 3.
>
> **Hosting and timing**
>
> 1. Can the company's current hosting run PHP 8.4 or newer, MySQL 8 or MariaDB 10.6 or newer, a cron job every minute, SSH or Git deployment, a subdomain with free SSL, and outgoing email (SMTP)? This decides whether we need to quote new hosting (Annex A item 5).
> 2. Kick-off on 8 Nov falls on Laxmi Puja. Could it move to 6 Nov or 12 Nov? And could draft lists of categories (marking which are restricted, A5), locations, and the notice text above the form (P3) be shared before signing, so we can load them early?
> 3. Will alert and acknowledgement emails (N1, N2) go through the company's own mail server or a transactional email service paid by the company?
> 4. Can the host turn off or shorten web server access logs for the grievance subdomain? These logs record visitors' IP addresses outside the application (S5).
>
> **Form fields**
>
> 5. For the optional fields in P6 (complainant type, sex, age group, ethnicity and disability status), which options should each offer, in Nepali and English? If the company or NBI already uses standard categories, for example in its own reporting, we would like to use the same ones. Each field will also offer "prefer not to say".
> 6. For the preferred contact method (P4), are phone call and email enough, or should SMS or another channel be offered? The system itself sends email only.
> 7. Is the offline channel list in A6 complete (phone, email, drop box, in person), or should others be added, such as a letter or a meeting with a manager?
> 8. Should the Nepali form take the incident date in Bikram Sambat (P5), and should Nepali be the default language of the public pages (P2)?
> 9. May an anonymous complainant leave an email address only to receive updates, or does anonymous mean no contact details at all (P4)?
>
> **Case handling**
>
> 10. For "15 working days" (A2): is Saturday the only weekly holiday, and which public holiday list applies?
> 11. We propose these Nepali labels for the A4 statuses and priorities. Please confirm or correct them:
>     Received – प्राप्त; Under assessment – मूल्याङ्कनमा; Under investigation – छानबिनमा; Referred / Out of scope – अन्यत्र पठाइएको / दायराबाहिर; Resolved – समाधान भएको; Closed – बन्द; Reopened – पुनः खोलिएको; Low – न्यून; Medium – मध्यम; High – उच्च; Critical – अति गम्भीर.
> 12. Who may export case data (N4), and who may remove a "restricted" mark from a case (A5)? We assume every role can export without names or contact details, only the Administrator can include them, and only the Administrator can remove a restricted mark.
> 13. Should the Administrator also receive new-case alerts (N1)? In particular, if the company has no restricted-case officer, should alerts for restricted cases go to the Administrator? Otherwise nobody is told about them. And should complainants who gave an email be told when staff reply? (The RFQ does not require it.)
> 14. Should the staff screens be in Nepali, English, or both with a switch? The RFQ asks for both languages on the public side (P2) only.
> 15. How long should closed cases and their attachments be kept? The RFQ does not set a retention period.
> 16. For grievances received offline (A6): how should someone who used the drop box get a reference number and PIN, for example a numbered form with a tear-off part? Should people who complained by phone or in person use the status page, or be kept updated by staff? In which language, and with what wording, should the printed slip be? Should staff ask the optional questions in P6?
>
> Kind regards,
> [name, company]

## For kick-off, not the clarification email

The RFQ names the company only to the selected vendor, so these wait for the award:

- The company's name and logo for public pages, emails and the admin panel. It also replaces `APP_NAME`, which today is the placeholder "Grievance handling" (since WP3) and shows in emails and staff authenticator apps.
- Final category, location and notice text, if not shared earlier (question 2).
- Name and email of the company's first Administrator for production.
- Admin panel colour, if the company wants its own (plan section 7: Filament keeps its default theme apart from logo and colour).

## Apply when answered

What the code assumes until NBI answers, and what to change after. Everything not built yet is noted with the work package that will build it.

| # | Answer | Assumption in the repo today | Change once answered | Done |
|---|---|---|---|---|
| 1 | Not answered yet | Company host passes; no hosting quoted | Annex A item 5 if it fails; `docs/technical-reference.md` hosting line; staging plan | [ ] |
| 2 | Lists received 2026-11-12: 19 categories with target days, two restricted by default (sexual harassment, exploitation and abuse; reporting wrongdoing), and 3 locations. The notice and the other public text also arrived. Plan 1.6, 1.7 (WP4 steps 1, 4) | Placeholder categories ("Sexual harassment or abuse" restricted) and locations in `Database\Seeders\PlaceholderListsSeeder`, run outside production only; the notice above the form is a marked draft (`notice.default` in `lang/*.json`) | Edit the lists in Settings (or the seeder before go-live); enter the notice in Settings, Public notice (P3); kick-off date in `docs/progress.md` | [ ] |
| 3 | Not answered yet | `MAIL_MAILER=array` locally (`.env.example`); never `log`, which writes recipients into `laravel.log` | `MAIL_*` settings in the production `.env` (WP3) | [ ] |
| 4 | Not answered. The brief asks that no complainant IP address be kept in application or server logs, which is what this question asks of the host | Not controlled by the app | Deployment notes (`docs/deploy.md`, WP3); otherwise a known limit in `docs/technical-reference.md` | [ ] |
| 5 | New lists for Who are you? (11 options), age group (18–24, 25–34, 35–44) and caste/ethnicity (adds Tharu); Sex and Disability unchanged; "Under 18" kept apart; groups under 5 hidden in reports. Two documents differ on some Nepali wording (C1 to C4, C9). Plan 1.1 (WP4 steps 1, 11) | Enums `ComplainantType`, `Sex`, `AgeGroup`, `Ethnicity`, `Disability` with placeholder options, each with "prefer not to say" (`Grievance::OPTIONAL_ANSWERS`) | Edit the cases in those enums and their labels in `lang/en.json` and `lang/ne.json`, before production: the codes are stored encrypted | [ ] |
| 6 | Phone, Email and "Do not contact me". Plan 1.6 (WP4 step 1); C20 asks about the acknowledgement | `App\Enums\ContactMethod`: phone, email | Add or remove a case in `ContactMethod`, labels in `lang/en.json` and `lang/ne.json` | [ ] |
| 7 | Adds Letter, Meeting, Referred by another organisation and Other; Phone becomes "Phone / hotline". Plan 1.2 (WP4 step 1) | `App\Enums\Channel`: web, phone, email, drop box, in person; offline entry offers all but web | Add a case in `Channel`, labels in `lang/en.json` and `lang/ne.json` | [ ] |
| 8 | Bikram Sambat on the Nepali form, AD on the English one; Nepali stays the default; all dates stored in AD; staff screens show BS with AD alongside; exports have both. Plan 1.3 (WP4 steps 3, 10, 12) | Nepali is the default (`APP_LOCALE=ne`); the incident date is AD in both languages, as day, month and year selects with "I don't remember the day" | Bikram Sambat: replace `resources/views/public/partials/incident-date.blade.php` and `StoreGrievanceRequest::prepareForValidation()`, add a `BsDate` converter (P5); `APP_LOCALE` if English becomes the default | [ ] |
| 9 | Not answered directly. The brief makes the status page the only route for anonymous complainants, so the assumption stands; to confirm | Anonymous means no name, phone or email: contact fields are hidden for an anonymous case and dropped by `StoreGrievanceRequest::caseRules()` | Allow an email for an anonymous case in `caseRules()` and the form, if NBI says so (P4) | [ ] |
| 10 | Saturday only; government holidays, with Sundays as working days, entered yearly by the focal person and checked by the Administrator. New: a target of 15, 30 or 45 working days per category, extensions by the Administrator, and the count restarting from the reopening date. Plan 1.4 (WP4 steps 2, 6) | `config/ghm.php`: `weekend_days` is Saturday only, `sla_days` is 15; `holidays` table is empty. The clock (`Status::isOpen()`) runs in Received, Under assessment, Under investigation and Reopened, stops at Resolved, Referred and Closed; a reopened case counts from the day it was received; `closed_at` is set at Closed and cleared whenever the case leaves Closed, at Reopened or any other status (`GrievanceObserver`) | `weekend_days`; holidays entered in settings (WP2 step 4); clock statuses in `Status::isOpen()`; then run `php artisan ghm:recount-working-days` once | [ ] |
| 11 | Four status labels change (Under assessment, Under investigation, Referred / Out of scope, Closed); priorities as drafted. Plan 1.6 (WP4 step 1) | Draft labels in `lang/ne.json` (`status.*`, `priority.*`) | Edit `lang/ne.json` only; mark them confirmed in `docs/requirements-traceability.md` | [ ] |
| 12 | As assumed: staff export only the cases they can see, and each export is logged. New: un-restricting needs a reason. The brief's section 8 says "Administrator only" for export (C6). Plan 1.5 (WP4 steps 5, 12) | `CLAUDE.md` rule: only the Administrator removes a manual restriction; export without names or contacts for other roles | `GrievancePolicy` (step 7; `exportPersonalData()` for exports) if NBI differs | [ ] |
| 13 | The Administrator is alerted for three events: a restricted case with no active restricted-case officer, Critical priority, and past target. Messages alert the assignee only. Reply emails to complainants are not asked for. Plan 1.8 (WP4 step 7) | No Administrator alerts, so a restricted case alerts nobody while the company has no active restricted-case officer (the deploy checks require one); no reply notification to complainants | `Grievance::alertStaff()` (drop the Administrator exclusion); a reply notification only if NBI asks for it | [ ] |
| 14 | English; a Nepali switch would be preferable (C14). Statuses and categories show Nepali beside English on the case page. Plan 1.9 (WP4 step 10) | The staff panel is in English (`App\Http\Middleware\SetPanelLocale`); `APP_LOCALE=ne` stays the public default | Nepali: change the locale in `SetPanelLocale`. A switch: a user setting read there instead | [ ] |
| 15 | 5 years after closure; deletion is manual, by the Administrator, and logged. Plan 2.6 (WP4 step 15; C27) | No cleanup job; nothing is deleted | A scheduled cleanup and its note in `docs/technical-reference.md` (WP3) | [ ] |
| 16 | Not answered yet | The slip is in English, with the reference, PIN, received date and a line for staff; staff hand it over, read it out or rely on the acknowledgement email; no P6 answers for offline cases; an anonymous drop-box case cannot be followed up. Staff choose the complainant's language for the email | Slip text in `RecordedCase` and its print view `resources/views/filament/slip-print.blade.php` (Impeccable); the P6 fields on `GrievanceForm` if staff should ask them; a drop-box form is printed outside the app | [ ] |
