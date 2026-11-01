# Clarification questions for NBI

Questions for the RFQ clarification email, due **2 Nov 2026** (RFQ section 1), and what to change in the repo once NBI answers. They gather plan section 11's questions and the ones found while building WP0.

| Email | Status |
|---|---|
| Clarification email | Not sent |
| NBI's answers | Waiting |

When the answers arrive: record each one in the "Answer" column below, apply the change listed for it, tick it off, and move any decision into `docs/progress.md`.

## Email text

Subject: Clarification questions: RFQ NBI/GHM/RFQ/01 – [your company name]

> Dear NBI team,
>
> Thank you for the RFQ for the online grievance handling system. Before we submit our quotation, we would be grateful for answers to the questions below. Requirement IDs refer to RFQ section 3.
>
> **Hosting and timing**
>
> 1. Can the company's current hosting run PHP 8.3 or newer, MySQL 8 or MariaDB 10.6 or newer, a cron job every minute, SSH or Git deployment, a subdomain with free SSL, and outgoing email (SMTP)? This decides whether we need to quote new hosting (Annex A item 5).
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
> 13. Should the Administrator also receive new-case alerts (N1)? And should complainants who gave an email be told when staff reply? (The RFQ does not require it.)
> 14. Should the staff screens be in Nepali, English, or both with a switch? The RFQ asks for both languages on the public side (P2) only.
> 15. How long should closed cases and their attachments be kept? The RFQ does not set a retention period.
>
> Kind regards,
> [name, company]

## For kick-off, not the clarification email

The RFQ names the company only to the selected vendor, so these wait for the award:

- The company's name and logo for public pages, emails and the admin panel. It also replaces `APP_NAME`, which today is the skeleton's `Laravel` and shows in staff authenticator apps.
- Final category, location and notice text, if not shared earlier (question 2).
- Name and email of the company's first Administrator for production.
- Admin panel colour, if the company wants its own (plan section 7: Filament keeps its default theme apart from logo and colour).

## Apply when answered

What the code assumes until NBI answers, and what to change after. Everything not built yet is noted with the work package that will build it.

| # | Answer | Assumption in the repo today | Change once answered | Done |
|---|---|---|---|---|
| 1 | | Company host passes; no hosting quoted | Annex A item 5 if it fails; `docs/technical-note.md` hosting line; staging plan | [ ] |
| 2 | | No categories, locations or notice text in the repo | Seeders for categories (with `is_restricted`) and locations; notice text setting (WP1, P3); kick-off date in `docs/progress.md` | [ ] |
| 3 | | `MAIL_MAILER=log` locally | `MAIL_*` settings in the production `.env` (WP3) | [ ] |
| 4 | | Not controlled by the app | Deployment notes (`docs/deploy.md`, WP3); otherwise a known limit in the technical note | [ ] |
| 5 | | Five encrypted text columns on `grievances`; no option lists | Enums `ComplainantType`, `Sex`, `AgeGroup`, `Ethnicity`, `Disability` in `app/Enums`, labels in `lang/en.json` and `lang/ne.json`, casts on `Grievance` (WP1, P6) | [ ] |
| 6 | | `preferred_contact` is plain text; factory uses `phone` | `ContactMethod` enum with the agreed options (WP1, P4) | [ ] |
| 7 | | `channel` is plain text; factory uses `phone` | `Channel` enum: the online form plus the agreed offline channels (WP2, A6) | [ ] |
| 8 | | Nepali is the default (`APP_LOCALE=ne`); BS dates planned for the Nepali form | Date input in the WP1 form (P5); `APP_LOCALE` if English becomes the default | [ ] |
| 9 | | Anonymous means no name, phone or email | P4 validation in the WP1 form | [ ] |
| 10 | | `config/ghm.php`: `weekend_days` is Saturday only, `sla_days` is 15; `holidays` table is empty | `weekend_days`; holiday seeder for the rest of 2026 and 2027 (WP2, A2) | [ ] |
| 11 | | Draft labels in `lang/ne.json` (`status.*`, `priority.*`) | Edit `lang/ne.json` only; mark them confirmed in `docs/requirements-traceability.md` | [ ] |
| 12 | | `CLAUDE.md` rule: only the Administrator removes a manual restriction; export without names or contacts for other roles | `GrievancePolicy` (step 7) and the export page (WP3, N4) if NBI differs | [ ] |
| 13 | | Administrator alerts are opt-in; no reply notification to complainants | Alert recipients (WP3, N1); a reply notification only if NBI asks for it | [ ] |
| 14 | | The staff panel is in English (`App\Http\Middleware\SetPanelLocale`); `APP_LOCALE=ne` stays the public default | Nepali: change the locale in `SetPanelLocale`. A switch: a user setting read there instead | [ ] |
| 15 | | No cleanup job; nothing is deleted | A scheduled cleanup and its note in `docs/technical-note.md` (WP3) | [ ] |
