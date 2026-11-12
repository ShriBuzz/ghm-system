# Change plan: the company's answers and Policy updates

Written 2026-11-12, when three documents arrived from the company (NEEK). Steps 1 and 2 of section 6 are built; the step status is in `docs/progress.md`. The work is WP4 (section 6). Step status goes in `docs/progress.md`, the requirement wording in `docs/rfq-requirements.md` ("After the RFQ"), and the status of each ID in `docs/requirements-traceability.md`.

**Decision, 2026-11-12: answers first.** The project lead chose to build now what answers our own clarification questions, together with what the company added while answering them. Everything that answers no question we asked is a new requirement, held until scope and price are agreed with NBI. Section 3 marks each ID's track. Section 6, "Answers first", has the items, the defaults for the conflicts and what to tell the company.

| Document (in `docs/`, not committed) | What it holds |
|---|---|
| `responses to whatsapp queries.pdf`, 6 pages | Answers to our clarification questions, sent by WhatsApp: option lists, channels, dates, working days, export and restriction rules, Nepali labels, the confidentiality notice, alerts, staff-screen language |
| `Brief_NEEKs Digital Grievance Handling System.pdf`, 10 pages | "Brief on NEEK's Online Grievance Handling System": eleven updates since the RFQ from the GHM Policy and SOP that UNDP approved, then roles and alerts, workflow, categories, form lists, the feedback step, public page text, reports and retention |
| `Grievances type.pdf`, 3 pages | Policy Annex A: each grievance type with its target time and who resolves it, and the matters the GHM does not accept |

The two main documents mostly agree. The Brief adds the feedback step, the export contents, dashboard indicators, retention and the full public text. Where they differ, section 4 lists the question for the company.

How to read this file:

- Section 1 takes the WhatsApp answers one by one, in the PDF's order. For each: what the company asked, what the system does today, what changes, and how much that affects the system.
- Section 2 covers what only the Brief and Annex A add.
- Section 3 gives the new requirement IDs U1 to U16. U1 to U11 are the rows of the Brief's "Updates since the RFQ" table, in its order.
- Section 4 lists the conflicts and questions to send back, section 5 the decisions for the project lead, and section 6 the work plan.

Two facts that affect everything below:

- **The Nepali in the PDFs cannot be copied.** The PDFs' font encoding garbles the Nepali text when it is copied (प्राप्त comes out as "Mाnत"), so every Nepali string has to be retyped. `docs/rfq-requirements.md` holds the strings retyped from the page images; check them against the company's source files (question C11) before they go live.
  - The PDFs were written in Word with Mangal, a Unicode Nepali font, and turned into PDFs with macOS's "Save as PDF", which loses the mapping for joined letters. The original Word files therefore hold clean Nepali text.
  - Get the `.docx` files, or text copied straight from WhatsApp messages; a re-saved PDF does not help.
  - Keep the files untracked, like the PDFs. The agent compares them with the retyped strings and lists every correction before they go into the language files or the seeder.
  - Checked 2026-11-12 against both Word files, `docs/Brief_NEEKs_Digital_Grievance_Handling_System.docx` and `docs/responses_to_whatsapp_queries.docx`. Both are untracked and hold clean Unicode Nepali.
  - All 124 of the company's Nepali strings in `docs/rfq-requirements.md` match one of the files exactly, so none needed correcting. The only other Nepali there is our three old draft status labels, kept for comparison.
  - The WhatsApp Word file says the same as its PDF and adds no requirement this plan lacks. Its Nepali age groups use a hyphen (१८-२४) where the brief uses an en dash (१८–२४). The en dash stays, as in the code today.
  - Both files are the company's internal documents, and the brief's names staff, so they stay out of git like the PDFs.
- **The repository is public** (checked 2026-11-12), and the Brief names staff members and describes internal policy. Keep the three PDFs out of git (section 5). This file and the other docs name roles, never people.

## How impact is rated

| Size | Meaning |
|---|---|
| None | The system already does it; at most a test or a line in a doc |
| Small | Up to half a day: labels, list values, one rule in one place |
| Medium | About a day: a new column or action across a few files, with tests and a browser walk |
| Large | More than a day: a new screen or flow, public-page work with design and accessibility review, or data-heavy reporting |

Effort is for one developer working with the agent. It includes the project's definition of done: a Pest test and a Playwright spec per ID, a walk per role at 360 px and desktop, `/ponytail-review`, and the traceability row. Every change lands before production, and only fake data exists today, so no real data needs migrating. Local and staging data are simply recreated.

The scope lines use three labels:

- "Answer": the company answered one of our questions about an RFQ requirement.
- "Addition": something the company added while answering one of our questions, on the same topic.
- "New": a requirement that answers no question we asked, from the Policy and SOP.

The contract has a fixed price. Answers and additions are built now; new items are held until scope and price are agreed (sections 5 and 6).

## 1. The WhatsApp answers, one by one

### 1.1 'About you' categories (P6, question 5)

What the company said:

- All five questions stay optional, each with "Prefer not to say".
- New lists for Who are you? (11 options), Age group and Caste/ethnicity. Sex and Disability keep their options. The exact English and Nepali are in `docs/rfq-requirements.md`, U8.
- Keep "Under 18" separate: it helps to spot possible child labour.
- In reports and on the summary page, no group with fewer than 5 cases is shown on its own. The Brief makes this exact: such a breakdown shows "<5".

What the system does today: five enums with placeholder options (`ComplainantType`, `Sex`, `AgeGroup`, `Ethnicity`, `Disability`), each with `prefer_not_to_say`. `Grievance::OPTIONAL_ANSWERS` is the one list the form, the rules, the case page and the export read. The chosen codes are stored encrypted. The summary page shows every count as it is.

| Question | Today | The company's list |
|---|---|---|
| Who are you? | Worker; Contractor or supplier; Community member; Other | Permanent employee; Temporary or probationary employee; Contract worker (e.g. security, cleaning, driver); Former employee; Supplier or supply-chain worker; Customer; Community member; Trade union; Worker representative; Civil society organisation; Other |
| Sex | Female; Male; Other | No change |
| Age group | Under 18; 18–29; 30–44; 45–59; 60 or over | Under 18; 18–24; 25–34; 35–44; 45–59; 60 and above |
| Ethnic group | Brahmin or Chhetri; Janajati; Madhesi; Dalit; Muslim; Other | Adds Tharu. The question becomes "Caste/ethnicity", and Janajati becomes "Janajati / Indigenous Nationalities" |
| Disability | Yes; No | Same options. The Nepali question changes, in one of two versions (C4) |
| "Prefer not to say" in Nepali | भन्न चाहन्नँ | भन्न चाहँदिन in the answers, भन्न नचाहने in the Brief (C3) |

Change A, the lists (U8):

- Replace the cases of `ComplainantType` and `AgeGroup`, and add `Ethnicity::Tharu`. Labels and question wording go in `lang/en.json` and `lang/ne.json` (`form.about.*`, `complainant_type.*`, `age_group.*`, `ethnicity.*`, `disability.*`).
- Update the tests that name old codes: `OptionalAnswersTest`, `PublicFormTest`, `ExportTest`, and any Playwright spec that picks an option.
- The public form gets longer select lists. The layout does not change.

Impact of A: **Small**, 2 to 3 hours. No migration. It must land before production: the codes are encrypted, so a code changed after real answers exist leaves those answers on the old list. Scope: Answer.

Change B, small groups hidden (U11):

- In `App\Filament\Pages\Summary`, a count from 1 to 4 shows as "<5", in every breakdown on the page, including the ones U13 adds (section 2.5).

Impact of B: **Small**, 1 to 2 hours. Scope: Addition, made with the answer to question 5. Risk: with an exact total, one hidden count can be worked out from the others. Full protection hides a second count too (C22).

Open: C1 (one option or three for trade union, worker representative and civil society), C2, C3, C4, C9 (the Brief's missing section 9 on caste and disability), C22.

### 1.2 Offline channels (A6, question 7)

What the company said: the offline list is Phone / hotline; Email; Drop box; In person; Letter; Meeting (e.g. Labour Relations Committee or community meeting); Referred by another organisation (e.g. Labour Office, NHRC or a union); Other. Website submissions are recorded automatically as "Online form".

What the system does today: the `Channel` enum has web, phone, email, drop_box and in_person. Offline entry offers every channel except web, and web shows as "Online form".

What changes (U8):

- Add four cases to `Channel`: `letter`, `meeting`, `referred` and `other`.
- Relabel phone as "Phone / hotline", and use the company's Nepali: drop box becomes उजुरी पेटिका, online form becomes अनलाइन फाराम.
- `OfflineEntryTest` covers one of the new channels.
- The case page, export and summary show the new labels without further changes.

Impact: **Small**, about 1 hour. The channel is stored as plain text, not encrypted, so a channel can be added safely even after go-live. Scope: Answer.

### 1.3 Dates and default language (P2, P5, question 8)

What the company said:

1. Open the public page in Nepali, with English one click away.
2. Use Bikram Sambat (BS) for the incident date on the Nepali form, and AD on the English form.
3. Store all dates in AD, so the working-day count and the holiday list stay correct.
4. On staff screens, show dates in BS with AD alongside, for example 2083-07-22 (2026-11-08).
5. Excel exports include both BS and AD date columns. The company reports to UNDP in AD.

What the system does today: points 1 and 3 already hold. The incident date is AD in both languages, as three selects with "I don't remember the day". Staff screens and the export show AD only.

What changes:

- Points 1 and 3: nothing.
- **BS converter (U9).** A new `App\Support\BsDate` converts AD to BS and back, using a table of BS month lengths per year. The plan named this class in section 5; it was never built. The table covers BS 2070 to 2100 and is taken from the government calendar. Tests: known pairs, including the company's own example 2083-07-22 = 2026-11-08, month and year boundaries, and refusal of impossible BS dates, such as day 32 in a 30-day month. No package: the table is data plus about 50 lines. If a maintained package is chosen at build time instead, it gets its line in `docs/technical-reference.md` and `docs/technical-note.md`.
- **Nepali form (P5).** `resources/views/public/partials/incident-date.blade.php` shows BS selects in Nepali: days 1 to 32, the twelve BS months, BS years. `StoreGrievanceRequest::prepareForValidation()` converts the date to AD before the existing rules run. "I don't remember the day" stays; the case then holds the first day of that BS month. A BS month straddles two AD months, so a new `incident_date_calendar` column (`bs` or `ad`) records which calendar the complainant used. The case page can then say "Kartik 2083, exact day not remembered", instead of an AD month that is half wrong.
- **Staff screens (U9).** One formatter shows BS first and AD in brackets, for every date staff read: case list, case page (received, closed, incident and the new resolved, due and feedback-due dates), notes, messages, activity log and holidays. Date inputs stay AD pickers: the summary range, the list filters and offline entry. That is an assumption, since the company asked only for display.
- **Export (U9, U14).** Each date column appears twice, AD and BS (section 2.4).

Impact: **Large**, about 2 days in all:

- The converter and the Nepali form take about a day. It is a public page, so it needs the Impeccable check and the 360 px walk in both languages.
- The staff screens take half a day.
- The export takes half a day, as part of U14.

Main risk: the BS table. Month lengths are fixed only a few years ahead, in the published calendar, so dates past the last published year can be a day out. The maintainer extends the table each year, at the same time as the holiday list (support checklist). A wrong table gives wrong incident dates and wrong BS display. Check every year it covers against the published calendar.

Scope: Answer, all of it. Question 8 asked whether the Nepali form should take Bikram Sambat and, if so, whether staff screens and exports should show it too. The export part is only the two date columns in both calendars; the other export columns stay held (U14).

Open: C24 (BS dates in offline entry).

### 1.4 Tracking working days (A2, question 10)

What the company said:

1. Saturday is the only weekly holiday.
2. Each type of grievance has its own target time of 15, 30 or 45 working days, set per category in Settings. The case list highlights cases past their own target.
3. The Administrator can extend a case's due date, with a reason recorded in the activity log. The Policy allows one extension of up to 15 working days. The Brief adds: "further extensions also by the Administrator, recorded as approved by the Grievance Redressal Committee".
4. Pausing at Resolved or Closed is fine. When a case is Reopened, the count restarts from the reopening date.
5. Public holidays are the government's, and Sundays are working days. Each year the focal person enters the new list and the Administrator checks it. The Administrator must be able to edit the list without a developer.

What the system does today:

- `config/ghm.php`: `weekend_days` is Saturday, and `sla_days` is 15 for every case.
- `working_days_open` counts from `received_at` (`WorkingDays::between()`). It freezes at Resolved, Referred and Closed, and `ghm:recount-working-days` recounts it nightly. A reopened case counts again from the day it was received.
- The case list shows a red badge and has an "Overdue only" filter for cases over 15 working days.
- Holidays are in Settings, Holidays, for the Administrator only (`HolidayPolicy`), who can add and delete them. The table is empty.
- There is no extension.

What changes, point by point:

1. Nothing.
2. **Target per category (U1).**
   - `categories.target_days` becomes a required field in `CategoryResource`; the company's 19 values come with the lists (1.6).
   - `grievances.target_days` is copied from the category when a case is created. A later change to a category's target then leaves past cases alone, so "resolved within target" in reports stays true.
   - The case list's badge and "Overdue" filter compare `working_days_open` with the case's `target_days + extended_days`. That is a column comparison in SQL, with no join. The filter's label becomes "Overdue only (past target)". The case page shows the target and the due date (`WorkingDays::add()`, new).
   - `sla_days` leaves the config.
   - Tests: `CaseListTest`, `WorkingDaysTest` and `SettingsTest`, plus the Playwright overdue badge (@A2) and category settings (@A7) specs.
3. **Extension (U2).**
   - New "Extend due date" action on the case page, for the Administrator only (`GrievancePolicy::extend()`) and on open cases only. It takes working days (1 to 15) and a required reason, and adds to `grievances.extended_days`.
   - The reason is kept as an internal note of kind "Extension" (encrypted, dated, with author; see note kinds below). The activity log entry holds the days and the note's ID, never the text.
   - A second extension needs a tick, "Approved by the Grievance Redressal Committee", which is logged too.
4. **Pause and restart (U3).**
   - Pausing already works.
   - For the restart, a new `grievances.reopened_at` column makes the clock count from it when set, from `received_at` otherwise. That changes `GrievanceObserver::updateClock()` and the nightly recount.
   - The comment and the tests that say a reopened case "counts again from the day it was received" change with it.
   - Referred / Out of scope also stops the clock today. The company did not mention it, so it stays (C19).
5. **Holidays.**
   - Editing by the Administrator already works without a developer.
   - Entry by the focal person is new. Either focal persons may add holidays (a one-line `HolidayPolicy` change, plus holiday changes in the activity log so the Administrator can check them), or the focal person prepares the list and the Administrator enters it, with no change (C15).
   - The holidays for the rest of BS 2083 must be entered before go-live. Government holidays are published in BS, so showing BS beside AD on the Holidays page (U9) makes that check easier.

Note kinds, shared by U2, U4, U5 and U12: a new `grievance_notes.kind` column (note, feedback, failed contact attempt, extension, reopening, restriction removed). Staff choose the first three in the note dialog, and the system sets the others. The Notes tab shows each kind as a badge. Note bodies stay encrypted (S3). The kind is a plain code, so the rules in U2, U4 and U12 can check it without reading note text.

Impact:

- U1 target per category: **Medium**, half a day to a day. Migrations on `categories` and `grievances`.
- U3 restart: **Small**, about 2 hours, built with U1.
- U2 extension: **Medium**, about a day, with the note kinds.
- Holidays: **None**, or **Small** if focal persons enter them.

Scope: Answer for points 1, 4 and 5. Question 10 asked whether the statuses that stop the count are right, and who keeps the holiday list up to date. New for points 2 and 3: the company presents them as Policy changes after UNDP's feedback, and they change A2.

Open: C15, C16 (the target for an appeal, and whether an extension carries over a reopening), C17 (the limit on later extensions), C19.

### 1.5 Data export and permissions (N4, A5, S6, question 12)

What the company said: the current setup works, with three additions:

1. Staff export only the cases they can see. Focal persons never export restricted cases, even without names, because the category itself is sensitive.
2. Only the Administrator un-restricts a case, with a reason that appears in the activity log. Any user can mark a case restricted.
3. Every export is recorded in the activity log, with who exported, when and what date range.

Also: the Administrator role is held by the company's Senior Manager, not by IT staff.

What the system does today:

1. Export goes through `Grievance::visibleTo()`, so focal persons cannot export a restricted case at all (`ExportTest`).
2. Every role can mark a case restricted, and only the Administrator removes a manual mark (`GrievancePolicy::removeRestriction()`). No reason is asked.
3. Each export is logged with the user, the time, the date range, the format, whether personal data was included and the number of cases, and again on each case (`CaseExport`).

What changes:

1. Nothing. But the Brief's section 8 says export is "Administrator only" and that restricted cases are exported only by the Administrator. That contradicts this answer and the Brief's own section 2 (C6).
2. **Reason for un-restricting (U5).** "Remove restricted mark" asks for a required reason. The reason is saved as an internal note (of kind "Restriction removed" once note kinds exist), and the activity log's `restriction_changed` entry carries the note's ID. The Activity log page shows the reason by reading that note. Why a note: the activity log holds codes and IDs only, never free text, because it is not encrypted and a reason can name people (S3, the project's privacy rules). Tests: `RestrictionTest` and `ActivityLogTest`.
3. Nothing.

The Senior Manager as Administrator: the user guide gains an Administrator part in plain words for a non-technical reader, covering settings, holidays, target days, extensions, removing restrictions, the export with names, and the activity log. Training covers it too. At go-live, the first Administrator account is the Senior Manager's. The IT contact gets server access only, with no panel login (Brief section 2). `docs/credentials-handover.md` and `docs/deploy.md` say who receives what.

Impact: points 1 and 3 **None**. Point 2 **Small**, about 2 hours, without note kinds. Docs **Small**. Scope: Answer for points 1 and 3. The reason is an Addition, made with the answer to question 12.

Open: C6.

### 1.6 Translations and field lists (A4, A7, P4, P5, questions 2 and 11)

What the company said: it gave the Nepali labels for statuses and priorities, 19 categories (with target days, two restricted by default), 3 locations, and the lists for how often, submission type and preferred contact. The exact lists are in `docs/rfq-requirements.md` (A4 and U8).

| List | Today | The company's version |
|---|---|---|
| Statuses in Nepali | Drafts | Four change: Under assessment मूल्याङ्कनमा → प्रारम्भिक मूल्याङ्कनको क्रममा; Under investigation छानबिनमा → छानबिनको क्रममा; Referred / Out of scope अन्यत्र पठाइएको / दायराबाहिर → अन्यत्र सिफारिश गरिएको / दायराबाहिर; Closed बन्द → बन्द गरिएको. Received, Resolved and Reopened stay |
| Priorities in Nepali | Drafts | All four as drafted |
| Categories | Five placeholders in `PlaceholderListsSeeder`, one restricted | 19, with target days of 15, 30 or 45. Restricted: no. 6, sexual harassment, exploitation and abuse, and no. 9, reporting wrongdoing |
| Locations | Head office; Production plant; Other | Head office, Thapathali; Factory, Hetauda; Other |
| How often | Once; More than once; Still happening | Once; Repeatedly (पटक-पटक); Still happening. The Nepali question becomes घटना कति पटक भएको हो? |
| Submission type | "No, stay anonymous" / "Yes, give my name and contact" | "Without my name (anonymous)" / "With my name" (नाम नखुलाई (बेनामी) / नाम सहित) |
| Preferred contact | Phone; Email | Adds "Do not contact me" (मलाई सम्पर्क नगर्नुहोस्) |

What changes (U8, with U1 and U5 for the category values):

- `lang/ne.json`: the four status labels (A4). `lang/*.json`: the recurrence, identity choice and contact labels.
- **Categories and locations.** `PlaceholderListsSeeder` becomes the real lists: both languages, target days, restricted flags and sort order. It may then run once in production, so nobody types 19 bilingual categories by hand at go-live. `firstOrCreate` keeps it safe to re-run. The `docs/deploy.md` step changes so production loads the lists too. Settings stay the way to edit them later.
- **"Do not contact me".** A new `ContactMethod::None`. `StoreGrievanceRequest::caseRules()` then needs its own "phone or email" rule (`required_without`), because a preferred method no longer guarantees that one was given. P7 sends the acknowledgement whenever an email is given. Assumption: it is still sent with "Do not contact me", because it carries the only copy of the reference and PIN besides the screen (C20).
- Tests: `CaseValuesTest` (A4 labels), `PublicFormTest`, `OfflineEntryTest`, `SettingsTest`, and any Playwright spec that picks a category or channel by name.

Impact: **Small to Medium**, about half a day. The labels and the seeder are quick. "Do not contact me" touches the validation rules shared by the public form and offline entry, the case page and the acknowledgement. Scope: Answer.

Open: C1, C5 (two Nepali versions of "Land and resources" and of the location "Other"), C20.

### 1.7 Confidentiality notice (P3), with the Brief's public page text (section 7)

What the company said. The Brief says "use this text exactly; NEEK may edit it later through the Administrator settings if that is built". The English and Nepali are in `docs/rfq-requirements.md`, U10.

- Page title: "Raise a grievance".
- Confidentiality notice above the form: the heading "Your grievance is safe with us." and four points (only trained staff see it; anonymous with the reference and PIN; no retaliation; sexual harassment cases seen only by a trained team that includes a woman officer).
- Emergency line at the top of the page: "In immediate danger? Call the police on 100."
- After submitting: thanks; keep the reference and PIN safe; "What happens next" (whether the grievance can be taken up within 7 working days, most resolved within 15 to 45 working days, progress can be checked at any time, and the company will ask whether the solution worked); "Free, and your rights stay with you".
- Status page title: "Check your grievance".
- Other ways to raise a grievance: phone, email, drop boxes at the head office and factory, or in person with the focal person. The company still has to send the hotline number and the grievance email address.

What the system does today:

- The notice is a setting (Settings, Public notice, both languages, plain paragraphs). Until it is saved, a draft marked as such shows.
- The form's title is "Send a grievance". There is no emergency line and no "other ways" text.
- The received page has our own wording (title, write both down, staff will look, Done).
- The status page's English title already matches; its Nepali does not.

What changes (U10):

- **The notice.** The Administrator enters it in Settings; that is data, not code. The form shows plain paragraphs only, and the company's notice is a heading with a bulleted list. So `form.blade.php` shows lines starting with "- " as a list and the first line as the heading, in place of "Before you start". The settings hint explains the format.
- **The emergency line.** A new line at the top of the public pages, with the number as a `tel:` link.
- **Other texts.** The page title, received page, status page title and "other ways" block are new and changed keys in `lang/en.json` and `lang/ne.json`. Our safety hints stay next to the company's text (the Done button's hint, "take a photo of this screen") unless the company objects. These protect the PIN on a shared phone.
- **Not built:** editing these other texts in Settings ("if that is built"). A developer changes them in the language files.
- **Checks.** Impeccable `clarify` and `critique` on the changed pages, axe, and 320 and 360 px in both languages. Update the Playwright specs that look for the old title or text.

P3 check: P3 asks the notice above the form to cover "what happens next". The Brief puts "What happens next" only on the page after submitting, while the WhatsApp version has it inside the notice. To keep P3, the plan shows that paragraph in the notice too (C7).

Impact: **Medium**, half a day to a day. These are public pages with design and accessibility checks, but the work is mostly text. Scope: Answer for the notice (P3), which goes in now as data. The page title, received page, status page title, emergency line at the top and "other ways" come from the brief's section 7, which answers no question we asked: New. The project lead moved them into the answers track on 2026-11-12 (next paragraph).

Open: C7, C8 (the Nepali "What happens next" has a sentence the English lacks), C12 (hotline number and email). C11 is settled: both Word files arrived.

The WhatsApp answers say "The confidentiality notice and other page text will follow separately", and the Brief then supplied that other text. So the company expects it to be used. It answers no question we asked, but the project lead moved it into the answers track on 2026-11-12 (item 18 in section 6).

The estimate:

- the wording (page title, received page, status page title) is 1 to 2 hours;
- the two new page elements (the emergency line at the top and the "other ways" block) add 2 to 3 hours, with the public-page design and accessibility checks.

That makes about half a day in all.

### 1.8 Internal notifications (N1, question 13)

What the company said:

- As before, no alert holds grievance details, only a prompt to sign in.
- A new case, not yet assigned, alerts all focal persons (the focal person and the backup) for non-restricted cases, and all restricted-case officers for restricted ones. Nobody waits for an assignment to be told.
- After assignment, new complainant messages alert the assigned user only, "as now".
- The Administrator is alerted only when:
  - (a) a restricted case arrives and no active restricted-case officer exists;
  - (b) a case is set to Critical (same day, SOP 7.1);
  - (c) a case passes its target time without being resolved (escalation, SOP 8.2).
- Not answered: whether complainants who gave an email are told when staff reply. The Brief does not ask for it either, so the assumption is no.

What the system does today (`Grievance::alertStaff()`, `CaseAlert`):

- New case: the active staff who can see it, except Administrators and whoever entered an offline case. This matches the company's rule.
- Complainant message: every active staff member who can see the case, except Administrators, whether or not it is assigned. **The answer's "as now" is a misunderstanding.** Today a message on an assigned case also alerts every other focal person.
- The Administrator is never alerted, so a restricted case alerts nobody while no restricted-case officer is active.

What changes (U6):

- **Messages.** A message goes to the assignee only, when the case has an active assignee who can see it; otherwise, the same people as for a new case. Assumption: when the assignee is the Administrator, the assignment wins and the Administrator gets the alert (C21).
- **(a)** In `alertStaff()` when a case is created: a restricted case with no active restricted-case officer alerts the active Administrators. Assumption: the same goes for a message on such a case, since otherwise nobody hears it (C21).
- **(b)** When the priority becomes Critical (`GrievanceObserver::updated`), the active Administrators are alerted, except one who set it.
- **(c)** The nightly recount alerts the active Administrators about an open case that has passed `target_days + extended_days`. A new `grievances.escalated_at` column limits this to one alert per crossing; it is cleared on reopening or extension. Each such alert is logged in the activity log as "escalated".
- `CaseAlert` gets a kind (new case, message, critical, past target) for its subject and line. It still never holds the reference, category, name or description.
- The deploy check that at least one restricted-case officer is active stays, but it is no longer the only safety net.
- Tests: `CaseAlertTest` (recipients for each event). `MailQueueTest` does not change.

Impact: **Medium**, about a day: one method, the observer, the nightly job, one column and the notification. Scope: Answer for (a) and the message routing, since question 13 asked who is told about new cases and replies. Addition for (b) and (c), made with that answer. Until U1 is agreed, (c) uses the RFQ's 15 working days as every case's target.

Open: C21.

### 1.9 Staff dashboard language (question 14)

What the company said: English is fine for the staff screens, and a Nepali toggle is "preferable". The Brief says: "The staff dashboard is in English." Make sure that:

1. the complainant's own text shows exactly as they entered it, in Nepali or English;
2. statuses and categories show the Nepali name next to the English on the case page, so staff know what the complainant sees;
3. staff can reply in Nepali (Unicode) without display problems.

What the system does today: the panel is in English (`SetPanelLocale`). The description and messages show decrypted as text; Nepali is stored as UTF-8 (utf8mb4). The case page shows the English status and category only.

What changes (U16):

1. Check, with a test, that line breaks and Devanagari in the description, messages and notes show as written. Filament text entries may fold line breaks; if so, add a pre-wrap style. One Pest test with multi-line Nepali text, and a Playwright check on the case page.
2. `GrievanceInfolist` shows the status as "Under assessment (प्रारम्भिक मूल्याङ्कनको क्रममा)" and the category as "Wages and benefits (तलब तथा सुविधा)". The Set status dialog does the same.
3. A test that a Nepali reply from the panel shows unchanged on the status page. `status.spec.ts` checks this today with English text only.

Not built: a Nepali toggle for the whole panel. The panel's labels are written in English in the PHP code, so a toggle means translating every panel string and adding a per-user setting: **Large**, 2 to 3 days. Recommended: a change request after go-live, if the company still wants it (C14).

Impact: **Small**, about half a day, without the toggle. Scope: Answer.

## 2. What only the Brief and Annex A add

### 2.1 The feedback step and closing a case (Brief sections 3.1 and 6), U4

What the company said:

- After the status becomes Resolved, the case shows a "Feedback due" date 30 calendar days later on the staff screens.
- Minimum: staff record the answers as an internal note headed "Feedback". The complainant can answer by a reply on the status page, which is the only route for anonymous complainants.
- Optional: a structured feedback form on the status page, shown only while the case is Resolved, with six questions (Brief section 6). The answers are saved to the case and included in the export.
- The status can change to Closed only after a Feedback note exists, or after notes record two failed contact attempts at least 5 working days apart.
- If the answers show the remedy was not carried out, the concern was not resolved, or the complainant was treated badly, staff set the case to Reopened.
- Check questions 4 and 6 against Policy section 10, which we do not have; the Policy wins.

What the system does today: nothing for feedback. Any status can follow any other, and no resolved date is kept.

What changes, the minimum (recommended now):

- `grievances.resolved_at`, set each time the status becomes Resolved.
- "Feedback due" (`resolved_at` plus 30 calendar days) on the case page, and as a column and filter on the case list for Resolved cases.
- Note kinds "Feedback" and "Failed contact attempt" in the note dialog (1.4).
- Set status refuses Closed unless a Feedback note exists since the last Resolved, or two failed-contact notes are at least 5 working days apart (`WorkingDays::between()`). The message says what is missing.
- On the status page, while the case is Resolved, a short line asks the complainant to say whether the solution worked, by reply.

What changes with the optional structured form, only if the company wants the feedback indicators (2.5):

- The six questions on the status page while Resolved. It is a public page: Impeccable, both languages, works without JavaScript, axe.
- The answers in their own table, with the free-text answer encrypted.
- Export columns, summary indicators, and an alert to the assignee when answers arrive.

Impact: the minimum is **Medium**, about a day. The structured form is **Large**, about 2 more days. Scope: New.

Open: C10 (Policy section 10), C13 (structured form or not), C18 (whether the closure rule also applies to Referred / Out of scope cases).

### 2.2 Status rules (Brief sections 3.1 and 3.3), U12

What the company said:

- Referred / Out of scope: the complainant must be told the reason through a reply. Annex A adds: within 7 working days, why it is not accepted and where to go instead, and they may appeal.
- Reopened (an appeal, an escalation or a recurrence): the clock restarts from the reopening date (U3), and the reason is recorded in an internal note.
- Appeal: lodged within 15 working days of the outcome, as a complainant message, after which staff set the case to Reopened. It is decided within 15 working days.

What the system does today: Set status is a bare select with no rules. The status page accepts a complainant message in any status, so an appeal sent as a message already reaches staff.

What changes:

- Choosing Referred / Out of scope asks for the reply to the complainant, which is sent as a staff message in the same step and shows on the status page.
- Choosing Reopened asks for the reason, saved as a note of kind "Reopening".
- A stopped case (Resolved, Referred, Closed) can return to an open status only through Reopened, so every restart of the clock has a reason. This is a design assumption the project lead can veto: it means a case set to Resolved by mistake is corrected by reopening it, with a reason.

Impact: **Small to Medium**, about half a day, with the note kinds. Scope: New.

Open: C16.

### 2.3 Priority rules and time frames (Brief sections 3.2 and 3.3, Annex A)

What the company said:

- Critical: same-day action, and the Administrator is alerted. This is U6 (b).
- High: acknowledge within 1 working day and assess within 2.
- Initial assessment within 7 working days of receipt. Offline grievances acknowledged within 3 working days.
- Categories 3, 6, 7, 10 and 17 can need same-day action; staff mark them Critical when there is immediate danger.

What the system does today: the priorities exist, with no time rules attached.

What changes: nothing beyond U6 (b). The assumption is that staff follow the 1-, 2-, 3- and 7-day steps, and the system does not track them; the user guide lists them. The public text promises an answer within 7 working days. If the company wants the case list to flag cases still Received or Under assessment after 7 working days, that is a small addition (C25).

Impact: **None**; docs only.

### 2.4 Export contents (Brief section 8), U14, with U7

What the company said. The export (Excel or CSV, "Administrator only") holds:

- the reference number and the date received (AD and BS);
- channel, location, category, priority and status;
- the date of each status change and the resolved date;
- days taken (working days), within target (yes/no), extension (yes/no) and reopened (yes/no);
- the About-you fields, and the feedback answers if the structured form is built.

Restricted cases are exported only by the Administrator, and are marked as restricted. The standard export has no names or contact details.

What the system does today: the RFQ's N4 columns, in AD only: Reference, then Name, Phone and Email when the Administrator chooses them, then Received, Closed, Channel, Category, Location, Priority, Status and the five About-you answers. Every role exports what it can see.

What changes:

- New columns: Received, Closed and Resolved, each in AD and BS; Restricted; one date column per status, the last date the case entered it, read from the activity log's status changes (assumed shape, C26); Days taken; Within target; Extension; Reopened; and the feedback answers, if built.
- N4's own columns stay, since the RFQ's list is the minimum.
- Who may export waits on C6.

Impact: **Medium**, about a day. The status dates come from the activity log, and every date appears twice. Scope: New.

Open: C6, C26.

### 2.5 Dashboard indicators (Brief section 8), U13

What the company said. By quarter, with a date filter:

- the number of grievances by category, location, channel and complainant type;
- a breakdown by sex and age group;
- average resolution time, the share resolved within target, and the numbers of extensions and of reopened cases;
- feedback results (the shares fully, partly or not resolved, average satisfaction, reports of negative treatment), which need the structured form;
- "<5" for any breakdown under 5.

What the system does today: the Summary page (N3) shows counts by status and by category for a date range, this month by default.

What changes:

- The new breakdowns and figures on the Summary page, with quarter shortcuts on the date range, and "<5" throughout (U11).
- The About-you answers are encrypted, so their counts are made in PHP after decrypting the visible cases in the range. At this volume (tens to hundreds of cases a year) that is fine. The plain columns count in SQL, as today.
- The feedback figures come only with the structured form.

Impact: **Large**, about a day to a day and a half. Scope: New. Nothing forces it before go-live: the first quarterly report covers the quarter that ends after go-live.

Open: C23 (calendar quarters or the Nepali fiscal year).

### 2.6 Retention and deletion (Brief section 8), U15, also question 15

What the company said:

- Keep case records for 5 years after closure. Deletion is manual, by the Administrator, after the Senior Manager's approval, and the audit log records it.
- In the same section, and true today: no IP addresses in application or server logs (S5; whether the host can turn off its access logs is still question 4), no third-party scripts on the public page, referrals recorded as internal notes with no separate module, and all data on the company's server and domain. The code is handed over under the MoU.

What the system does today: nothing is ever deleted, because no policy allows a delete.

What changes: an Administrator-only "Delete case", for cases closed more than 5 years ago. It deletes the case with its messages, notes, attachments and files, and logs the reference and who deleted it. No case can qualify before late 2031, so the alternative is to write the rule into the technical note for the maintainer and build the action later. Recommended: the note now, the action later (C27).

Impact: **Small**, about half a day, if built now; **None**, docs only, if deferred. Scope: New, held. Question 15 was not among the questions sent by WhatsApp, and the rule comes from the brief.

### 2.7 Roles and who holds them (Brief section 2)

- Administrator: the Senior Manager.
- Focal person: the focal person, plus a backup.
- Restricted-case officers: a team that includes at least one woman.
- No login: the IT contact, who has server access only, and the investigation panel, whose findings the focal person records as notes.

This matches A1 and the three roles; the system does not change. The user guide gains the Administrator part (1.5), and the credentials sheet changes. Checking that a woman is among the restricted-case officers is the company's task, not a system rule.

Impact: **None**; docs only.

### 2.8 Annex A: who resolves, matters not accepted, appeals

- "Resolved by" names committees and people outside the system. Their decisions are recorded as notes, with no change.
- Matters the GHM does not accept: within 7 working days, the complainant is told why and where to go instead. That is Referred / Out of scope with a reply (U12). Possible crimes go to the police, which is process.
- Appeals are covered by U12 and C16.
- Annex A's type 9, "Whistleblowing on wrongdoing", is the Brief's category 9, "Reporting wrongdoing (fraud, corruption, breach of law)"; the system uses the Brief's name. Annex A has no "Other"; the Brief adds it, at 30 days.

Impact: **None**; user guide only (the matters not accepted, and who resolves each type).

## 3. New requirement IDs

U1 to U11 are the Brief's "Updates since the RFQ" table, row by row. U12 to U16 come from the Brief's later sections and the answers. Use them like the RFQ IDs: in commit messages (`feat(U1): target time per category`), as Pest groups (`->group('U1')`) and as Playwright tags (`@U1`).

Track, as decided on 2026-11-12 (section 6, "Answers first"):

- **Answer:** what one of our clarification questions asked. Built now.
- **Addition:** what the company added while answering one of our questions, on the same topic. Built now.
- **New:** answers no question we asked. Held until scope and price are agreed with NBI.

The question numbers are those in `docs/clarification-questions.md`. The WhatsApp answers follow `docs/client-discussion.md`, which has the same numbers.

| ID | Change | RFQ rows it changes | Built today | Track | Size | Estimate |
|---|---|---|---|---|---|---|
| U1 | Target time per category; case list highlights cases past their own target | A2, A7 | No (fixed 15 days) | New: held. It came with the answer to question 10, but the company presents it as a Policy change after UNDP's feedback, and it changes A2 | Medium | 0.5 to 1 day |
| U2 | Administrator extends a case's due date, with a reason | A2, A3 | No | New: held, as U1 | Medium | about 1 day, with note kinds |
| U3 | Clock pauses at Resolved or Closed, restarts from the reopening date | A2 | Pause yes, restart no | Answer (question 10, which statuses stop the count): now | Small | 2 hours |
| U4 | Feedback step before closure; closure rule | A3, A4, P8 | No | New: held | Medium; Large with the structured form | 1 day; form 2 more |
| U5 | Two categories restricted by default; un-restrict only by the Administrator, with a reason | A5, A7 | All but the reason | Answer (question 2) for the defaults; Addition (question 12) for the reason: now | Small | 2 to 3 hours |
| U6 | Alerts: new unassigned cases to all relevant users, messages to the assignee, the Administrator for three events | N1 | New-case alerts only | Answer (question 13) for the message routing and the restricted-case alert; Addition for the Critical and past-target alerts: now | Medium | 1 day |
| U7 | Export limits: users export only cases they can see; every export logged | N4, S6 | Yes | Built. Who may export waits on C6 | None | 0 to 2 hours |
| U8 | Option lists and Nepali labels | P4, P5, P6, A4, A6, A7 | Placeholders | Answer (questions 2, 5, 6, 7 and 11): now | Small to Medium | 0.5 day |
| U9 | Dates: BS on the Nepali form, AD on the English one, stored in AD, both on staff screens and in exports | P5, A2, A3, N4 | AD storage and Nepali default yes, BS no | Answer (question 8 asked about the form, staff screens and exports): now | Large | 2 days |
| U10 | Public text: confidentiality notice and messages | P3, P7, P8 | The notice setting only | Answer for the notice (P3), entered as data: now. New for the page title, received page, status page title, emergency line and "other ways" (brief section 7), moved into the answers track by the project lead on 2026-11-12: now | Medium | Entry, plus about half a day |
| U11 | Groups under 5 hidden in summaries | N3 | No | Addition (question 5): now | Small | 1 to 2 hours |
| U12 | Status rules: a reply for Referred, a reason for Reopened, appeals | A3, A4 | No | New: held | Small to Medium | 0.5 day |
| U13 | Dashboard indicators by quarter | N3 | Status and category counts only | New: held | Large | 1 to 1.5 days |
| U14 | Export contents | N4 | The RFQ's columns only | Answer (question 8) for the dates in both calendars: now. New for the other columns: held | Medium | 2 to 3 hours now; 1 day held |
| U15 | Records kept 5 years after closure; manual deletion by the Administrator | S6 | Nothing deleted | New: held. Question 15 was not among the questions sent by WhatsApp; the rule comes from the brief | Small, or docs only | 0.5 day, or 1 hour |
| U16 | Staff screens: complainant text as entered, Nepali beside English on the case page, Nepali replies | A3 | Partly | Answer and Addition (question 14): now. A Nepali staff panel is New and held: the answer says English is fine | Small | 0.5 day |

Totals:

- Now (answers, additions and the brief's public text): about 6 working days.
- Held (new): about 5.5 to 6.5 days, plus 2 for the structured feedback form and 2 to 3 for a Nepali panel.

Docs and the walks are included.

### Held items checked against the RFQ (2026-11-12)

Before the plan was committed, each held item was checked against the RFQ's sections 2 and 3, word for word in `docs/rfq-requirements.md`, to confirm it is new. The check searched the RFQ text for each item's terms and read every RFQ row they touch.

| Held item | What the RFQ says | Verdict |
|---|---|---|
| U1, target time per category | A2: "highlights cases open more than 15 working days", one limit for every case. A7's settings list users, categories (and which are restricted) and locations, with no target times | New. It replaces A2's single limit; A2 as written is built |
| U2, due-date extension | Nothing: no due date and no extension anywhere | New |
| U4, feedback step and closure rule | A4 lists the statuses only, with no rule for closing. P8 lets the complainant "add more information or a reply" | New: the "Feedback due" date, the closure rule and the structured form. The minimum route, a complainant answering by reply, is P8 and is built |
| U12, status rules | A3: set status, internal notes, replies. P8: complainant replies. Nothing ties a reply or a note to a status | New: requiring a reply for Referred and a reason for Reopened. The reply, the note and an appeal sent as a message are RFQ features and are built |
| U13, dashboard indicators | N3: "number of cases by status and by category, for a chosen date range" | New: location, channel, complainant type, sex and age group breakdowns, resolution time, share within target, extensions, reopened, feedback, quarters. N3's own counts and date range are built |
| U14, export columns other than the dates | N4: reference, dates received and closed, channel, category, location, priority, status, the five About-you fields; names only if the Administrator chooses | New: date of each status change, resolved date, days taken, within target, extension, reopened, a restricted mark, feedback answers. N4's columns are built |
| U15, retention and deletion | Nothing on keeping or deleting case records (S7 is about backups) | New |
| U10, the brief's other public text | P3: the notice above the form on confidentiality, no retaliation and what happens next. P7: the reference and PIN "with advice to note them down" | New as content. The emergency line and "other ways" are new elements. The page title, received page and status page wording replace our own text on pages the RFQ already has (P1, P7, P8); the function is built. Moved into the answers track on 2026-11-12 |
| A Nepali staff panel | P2's language switch sits under "3.1 Public side" only | New |
| Note kinds | Not a requirement: they are what U2, U4 and U12 need | Held with them |

Result:

- Every held item is new in its held part.
- Where a held ID overlaps the RFQ, that part (A2's 15 days, N3's counts, N4's columns, P8's replies) is already built. So holding them leaves no RFQ requirement unmet.
- The additions built now (U5's reason, U11, U6's Critical and past-target alerts, and the Nepali names in U16) are also beyond the RFQ. They are built because the company asked for them while answering our questions, and each is small. So is the brief's public text (U10), which the project lead moved in because the answers announced it.
- The RFQ never mentions Bikram Sambat, so U9 is not an RFQ requirement either. It is built because our question 8 offered it and the company answered.

## 4. Conflicts and questions for the company

To send back in one message. Listed by urgency: the first group decides stored codes and the public text, so it blocks go-live.

Before production (stored codes and public text):

- **C1.** Who are you?: three options (Trade union; Worker representative; Civil society organisation), as in the answers, or one ("Trade union, worker representative or civil society organisation"), as in the Brief? We suggest three, since reports can group them later but never split them.
- **C2.** Contract worker in Nepali: with the examples, "करार कामदार, जस्तै सुरक्षाकर्मी, सरसफाई, चालक आदि" (answers), or "करार कामदार" alone (Brief)?
- **C3.** "Prefer not to say" in Nepali: भन्न चाहँदिन (answers) or भन्न नचाहने (Brief)?
- **C4.** The disability question in Nepali: "के तपाईं अपाङ्गता भएको व्यक्ति हुनुहुन्छ? हो / होइन" (answers) or "के तपाईंमा अपाङ्गता छ? छ / छैन" (Brief)?
- **C5.** "Land and resources" in Nepali: भूमि तथा प्राकृतिक स्रोत (answers) or जग्गा तथा प्राकृतिक स्रोत (Brief)? The location "Other": अन्यत् or अन्य?
- **C7.** P3 asks the notice above the form to say what happens next. May the "What happens next" paragraph appear in the notice above the form as well as after submitting?
- **C8.** The Nepali "What happens next" adds "as soon as the grievance is registered you will get a reference number", which the English lacks. Keep it, or match the English?
- **C9.** The Brief refers to "section 9" for caste/ethnicity and disability, but has no section 9. What does it say?
- **C11.** Settled 2026-11-12: the Word files of the Brief and of the WhatsApp answers arrived, and every Nepali string was checked against them. Nothing to send.
- **C12.** The hotline number and the grievance email address for "Other ways to raise a grievance".

Rules (decide the code):

- **C6.** Who exports? The Brief's section 8 says "Administrator only", and that restricted cases are exported only by the Administrator. The Brief's section 2 and the answers say focal persons export non-sensitive data for the cases they can see, and restricted-case officers act "as focal person, for restricted cases". Today every role exports what it can see, with names only for the Administrator. That matches the RFQ, whose section 2 says the trained staff "export data", and whose N4 leaves names out unless the Administrator chooses them.
- **C10.** Please send the Policy (section 10, for the feedback questions) and the SOP (sections 6, 7.1, 8.2 and 9), which the Brief cites.
- **C13.** Do you want the structured feedback form on the status page? The feedback figures on the dashboard need it.
- **C14.** The staff screens stay in English. Is a Nepali switch wanted later, at extra cost?
- **C15.** Holidays: does the focal person enter them in the system, with the Administrator checking, or prepare the list for the Administrator to enter?
- **C16.** A reopened case: is its target the category's again, or 15 working days for an appeal (Annex A)? Does an earlier extension carry over?
- **C17.** Is each extension after the first also limited to 15 working days?
- **C18.** Does the closure rule (a feedback note or two failed contacts) also apply to Referred / Out of scope cases?
- **C19.** Referred / Out of scope stops the clock today, like Resolved and Closed. Is that right?
- **C20.** If a complainant chooses "Do not contact me" but gives an email, do we still email the reference and PIN?
- **C21.** Messages on a case assigned to the Administrator: alert the Administrator? Messages on a restricted case while no restricted-case officer is active: alert the Administrator?
- **C22.** Fewer than 5: may a 0 show as 0? With an exact total, one hidden count can be worked out from the others: should a second count be hidden too?
- **C23.** Dashboard quarters: calendar quarters (AD), or the Nepali fiscal year?
- **C24.** Offline entry: may staff enter the incident date in BS when the complainant gives one? (We suggest yes.)
- **C25.** Should the case list flag cases not assessed within 7 working days?
- **C26.** "Date of each status change" in the export: one column per status, with the last date the case entered it?

Still open from the first questions: 1 (hosting; staging is planned for 16 to 18 Nov), 3 (email sending), 4 (the host's access logs), 9 (an email for anonymous complainants: the Brief implies no, so we keep "anonymous means no contact details"; please confirm) and 16 (how offline complainants follow their case, and the slip's language). Plus the company's full name and logo for public pages and emails (the text says NEEK, while `APP_NAME` is still the placeholder), and, at go-live, the first Administrator's account details.

## 5. Decisions for the project lead

- **Scope and price.** The RFQ is fixed price, and items marked New in section 3 are new requirements from the Policy and SOP. Plan section 11 says: "anything new is logged as a change request outside the fixed price". Decide which new items go in at no charge, which become change requests, and which wait for support. Decided for now (2026-11-12): the answers and the company's additions to them go first, and the new items wait (section 6, "Answers first").
- **Timeline.** About 3.5 working days remain before the 18 Nov test version (12, 13, 16 and 17 Nov: 14 Nov is a Saturday and 15 Nov is Chhath). About 5 more remain before go-live on 27 Nov, and those also hold UAT, fixes, training on 25 Nov and the production deploy. The full list takes 10 to 12 days, so not everything fits. The answers track is about 6 days, ordered in section 6. The RFQ asks the vendor to tell NBI at once if a date is at risk.
- **The PDFs and the public repository.** The repository is public, and the Brief names staff and holds internal policy. Do not commit the three PDFs, or the two Word files, while the repository is public. Keep them in the vendor's private workspace, or make the repository private first (open question 2 in `docs/progress.md`). Until then, keep them untracked.
- **The shared plan doc.** `docs/implementation-plan.md` now has a section 12 that the shared plan doc lacks. Add it there before the file is next regenerated.
- **Design choices made in this plan**, to confirm or veto before WP4 starts:
  - target days copied onto each case at creation;
  - every reason (extension, un-restricting, reopening) kept as a typed internal note, with the activity log pointing at it;
  - BS conversion in our own class with a data table, not a package;
  - a stopped case reopened only through Reopened.
- **C27, retention.** Build the Administrator's delete action now, or only write the 5-year rule into the technical note and build the action during support? Recommended: the note now. No case can qualify before late 2031.

## 6. Work plan: WP4

Branches `wp4/<slug>` from `main-backup`, with pull requests into `main-backup`. Each step:

- starts with a failing Pest test grouped by its ID;
- adds a Playwright spec tagged `@U<n>` for anything a user sees;
- is walked per role at 360 px and desktop, reviewed with `/ponytail-review`, and gets its traceability row and the docs listed for it.

### Answers first (decided 2026-11-12, widened the same day)

The project lead's decision:

- **Built now:** what answers our own clarification questions, and what the company added while answering them, on the same topic.
- **Held:** everything that answers no question we asked, until scope and price are agreed with NBI.

Section 3's Track column marks each ID. The question wording that counts is `docs/client-discussion.md`'s, which the WhatsApp answers follow.

On 2026-11-12 the project lead also moved the brief's public page text (U10) into the track, because the answers announced it (item 18).

The track takes the answer and addition parts of steps 1, 2, 3, 4, 5, 7, 10, 11 and 12 in the full list below, and keeps their step numbers. It is about 6 working days.

| # | Change | Why now | Step, branch | Estimate |
|---|---|---|---|---|
| 1 | The company's "About you" lists: Who are you? (11 options), the new age groups, Tharu added | Answer, question 5 | 1, `wp4/answers-lists` | 2 to 3 hours |
| 2 | Channels: Letter, Meeting, Referred by another organisation, Other; Phone becomes "Phone / hotline" | Answer, question 7 | 1 | 1 hour |
| 3 | The four corrected Nepali status labels | Answer, question 11 | 1 | 15 minutes |
| 4 | "Do not contact me", with its own rule that a named complainant gives a phone or an email; the company's wording for how often and for the anonymous or named choice | Answer, question 6 and the company's lists | 1 | 2 hours |
| 5 | The 19 categories (two restricted) and the 3 locations in the seeder, which may then run in production; no target days | Answer, question 2 | 1 | 1 to 2 hours |
| 6 | The count restarts from the reopening date (`reopened_at`), in the observer and the nightly recount | Answer, question 10 | 2, `wp4/working-days` | 2 hours |
| 7 | Holidays: focal persons may add and edit them, and holiday changes go into the activity log so the Administrator can check them | Answer, question 10 | 2 | 1 to 2 hours |
| 8 | Bikram Sambat incident date on the Nepali form: `BsDate`, the BS selects, conversion before validation, and a month-only BS date shown as a BS month on the case page | Answer, question 8 | 3, `wp4/bs-incident-date` | 1 day |
| 9 | The notice from the WhatsApp answers entered in Settings: its heading, four points, what happens next and the free service. Its police line moves to the top of the page (item 18), so it is not shown twice. Covers P3 with no code | Answer, P3 | 4, data entry at each deploy | Entry only |
| 10 | A reason when the Administrator removes a restriction, kept as an internal note; the activity log holds the note's ID, never the text (note kinds stay held) | Addition, question 12 | 5, `wp4/unrestrict-reason` | 2 hours |
| 11 | The Administrator is alerted when a restricted case arrives and no active restricted-case officer exists | Answer, question 13 | 7, `wp4/alerts` | 2 hours |
| 12 | A complainant message on a case with an active assignee who can see it alerts the assignee only; otherwise the same people as a new case | Answer, question 13 | 7 | 2 to 3 hours |
| 13 | The Administrator is alerted when a case is set to Critical, and when an open case passes its target, once per crossing (`escalated_at`). Until U1 is agreed, the target is the RFQ's 15 working days | Addition, question 13 | 7 | half a day |
| 14 | Case page: Nepali beside English for status and category; tests that Nepali text, line breaks and replies show as written | Answer and addition, question 14 | 10, `wp4/case-page-nepali` | 3 to 4 hours |
| 15 | BS with AD alongside on staff screens: case list, case page, notes, messages, activity log and holidays; date inputs stay AD | Answer, question 8 | 10 | half a day |
| 16 | Export: the Received and Closed dates in both AD and BS | Answer, question 8 | 12, `wp4/export-dates` | 2 to 3 hours |
| 17 | Summary: counts from 1 to 4 shown as "<5" | Addition, question 5 | 11, `wp4/summary-small-groups` | 1 to 2 hours |
| 18 | The brief's public page text (section 7): the page title "Raise a grievance", the received page's wording, the status page title, the emergency line "In immediate danger? Call the police on 100." at the top of every public page, and the "other ways to raise a grievance" block on the form page. Our hints that protect the PIN on a shared phone stay | New, moved in by the project lead (the answers announced it) | 4, `wp4/public-text` | about half a day: wording 1 to 2 hours, the two new elements 2 to 3 |

Order. Each step follows the definition of done in `CLAUDE.md`.

1. Aim to finish before the 18 Nov test version: steps 1, 3, 2, 10 and 12 (items 1 to 8 and 14 to 16), about 3.5 days. Whatever does not fit moves to the UAT window.
2. In the UAT window: steps 7, 5, 11 and 4 (items 10 to 13, 17 and 18), about 2 days. Step 4 moves before 18 Nov if there is room, since testers see the public pages first.
3. Item 9 is entered on staging at its deploy, and in production at go-live.
4. Then step 16 closes the track.

Defaults where the documents disagree, or a rule is not settled. Each can change before go-live; only C1 touches stored codes, and only fake data exists until then.

- C1: three separate options (Trade union; Worker representative; Civil society organisation), as in the answers.
- C2 to C5: the WhatsApp answers' Nepali wording. The company's Word files confirm both versions, so these are real differences between its two documents, not typing slips.
- C7: "what happens next" sits in the notice above the form, as the answers have it and as P3 requires.
- C9: build the lists as given, and revisit them when the company sends its section 9.
- C15: focal persons may add and edit holidays; deleting one stays with the Administrator.
- C20: with "Do not contact me", the acknowledgement is still emailed if an email was given, as P7 requires.
- C21: a message on a case assigned to the Administrator alerts the Administrator. A message on a restricted case while no restricted-case officer is active also alerts the Administrator, as a new case does.
- C22: a 0 shows as 0; only counts from 1 to 4 become "<5".
- Item 18's placement, which the brief does not fix beyond "top of the page":
  - The emergency line goes in the public layout, so every public page shows it, with 100 as a `tel:` link.
  - The "other ways" block goes on the form page, below the form. It shows the company's text now; the hotline number and grievance email address are added when they arrive (C12).

Held as new requirements (they answer no question we asked):

- U1 (target days per category) and U2 (extensions). They came with the answer to question 10, but the company presents them as Policy changes after UNDP's feedback, and they change RFQ A2.
- U4 (the feedback step and closure rule) and U12 (status rules), with the note kinds they need.
- U13 (dashboard indicators), and U14's columns other than the two dates.
- U15: the 5-year rule and the delete action (question 15 was not in the WhatsApp set).
- A Nepali staff panel (question 14: "English is fine").

Tell the company, while the new items are held: every open case is flagged after 15 working days, as RFQ A2 says, including cases in the 30- and 45-day categories. The Administrator's past-target alert uses the same 15 days.

Waiting, without blocking the track:

- The Bikram Sambat month-length table, checked against the published calendar before item 8 is merged.
- Questions 1 and 3 (hosting, email), which staging needs; questions 4 and 16; C12 (the hotline number and email address).

### The full step list

These steps were the first proposal, in three phases. The Phase column below is that proposal; for now "Answers first" above, and section 3's Track column, replace it.

Phase 1 was proposed for the 18 Nov test version, because it settles stored codes, the forms testers use first, and the case clock. Phase 2 was to follow during UAT if NBI agreed, otherwise straight after go-live. Phase 3 was to come after go-live, as change requests or support work.

| Step | Work | IDs | Phase | Needs | Estimate | Docs to update when built |
|---|---|---|---|---|---|---|
| 1 | Lists and labels: the five About-you lists, channels, how often, submission type, contact with "Do not contact me", Nepali status labels; the real categories (target days, restricted flags) and locations in the seeder, allowed once in production | U8, U5, A4, A6, P4, P6 | 1 | C1, C3 to C5, C9, C20 | 0.5 day | `docs/deploy.md` (seeder), `docs/manual-testing.md`, `docs/user-guide.md` (lists), screenshots if the form changes |
| 2 | Target per category, and the clock restarting on reopening | U1, U3, A2 | 1 | step 1 | 0.5 to 0.75 day | `docs/user-guide.md` (days open, overdue), `docs/manual-testing.md`, `docs/technical-reference.md` (clock), `docs/database-schema.sql` |
| 3 | `BsDate` and the Nepali form's incident date | U9, P5 | 1 | the BS table checked | 1 day | `docs/technical-reference.md`, support checklist (extend the table yearly), `docs/manual-testing.md` |
| 4 | Public page text: notice format, emergency line, title, received page, status page, "other ways" | U10, P3 | 1 or 2 | C7, C8, C11, C12 | 0.5 to 1 day | `docs/manual-testing.md`, `DESIGN.md` if the layout changes |
| 5 | Note kinds; the reason for removing a restriction | U5, S6 | 2 | | 0.5 day | `docs/user-guide.md` (notes, restrictions), `docs/technical-reference.md` (log holds note IDs) |
| 6 | Due-date extension | U2 | 2 | steps 2, 5; C17 | 0.75 day | `docs/user-guide.md` (Administrator part) |
| 7 | Alerts: messages to the assignee, the three Administrator alerts, escalation once per crossing | U6, N1 | 2 | step 2; C21 | 1 day | `docs/user-guide.md` (alerts), `docs/deploy.md` (restricted-officer check), `docs/manual-testing.md` |
| 8 | Status rules: a reply for Referred, a reason for Reopened, stopped cases reopened only through Reopened | U12 | 2 | step 5; C16, C19 | 0.5 day | `docs/user-guide.md` (statuses) |
| 9 | Resolved date, "Feedback due", the closure rule, the status page prompt | U4 | 2 | steps 5, 8; C18 | 1 day | `docs/user-guide.md` (feedback, closing) |
| 10 | Case page: Nepali beside English, text as entered, Nepali replies; BS dates on staff screens and the Holidays page | U16, U9 | 2 | step 3 | 1 day | `docs/user-guide.md` (screenshots) |
| 11 | Summary: under 5 shown as "<5" | U11, N3 | 2 | C22 | 0.25 day | `docs/user-guide.md` (summary) |
| 12 | Export: new columns, AD and BS, the restricted mark; who may export | U14, U9, U7, N4 | 3 | steps 2, 3, 6, 9; C6, C26 | 1 day | `docs/user-guide.md` (export), `docs/manual-testing.md` |
| 13 | Dashboard indicators by quarter | U13, N3 | 3 | steps 2, 6, 11; C23 | 1 to 1.5 days | `docs/user-guide.md` (summary) |
| 14 | Structured feedback form, if wanted | U4 | 3 | step 9; C10, C13 | 2 days | `docs/manual-testing.md`, `DESIGN.md`, `docs/user-guide.md` |
| 15 | Retention: the rule in the technical note; the delete action later | U15 | 3 | C27 | 1 hour, or 0.5 day | `docs/technical-note.md`, `docs/technical-reference.md` |
| 16 | Close WP4: Administrator part of the user guide, schema regenerated, credentials sheet (who gets what), traceability, walk per role, `/ponytail-review`, the sync to `main` | all | each phase | | 0.5 day per phase | `docs/user-guide.md`, `docs/database-schema.sql`, `docs/credentials-handover.md`, `docs/requirements-traceability.md` |

Phase 1 is steps 1 to 4, about 2.5 to 3.25 days. Phase 2 is steps 5 to 11, about 5 days. Phase 3 is steps 12 to 15, about 3.5 to 5 days, plus a Nepali panel if the company asks for one (C14).

## 7. Docs updated with this plan (2026-11-12)

| File | Change |
|---|---|
| `docs/answers-change-plan.md` | This file (dev-only; added to `DEV_ONLY` in `scripts/sync-main.sh`) |
| `docs/rfq-requirements.md` | Confirmed Nepali labels for A4; "After the RFQ": U1 to U16 wording and the company's lists |
| `docs/requirements-traceability.md` | Rows U1 to U16; notes on the RFQ rows the answers change |
| `docs/clarification-questions.md` | Answers recorded per question; follow-up questions pointed here |
| `docs/client-discussion.md` | Decision lines filled in |
| `docs/progress.md` | WP4 table, open questions, the design choices to confirm |
| `docs/handoff.md` | Where things stand, next steps, read first |
| `docs/implementation-plan.md` | Section 12, which the shared plan doc still lacks |
| `CLAUDE.md` | Order of authority and scope include U1 to U16 |

The other docs (user guide, manual testing, technical reference and note, deploy, schema, credentials sheet) describe the system as built. They change step by step, as listed in section 6.
