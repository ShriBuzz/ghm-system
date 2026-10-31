# RFQ NBI/GHM/RFQ/01: requirements

Copied word for word from the client's RFQ (Request for Quotation: Development of an Online Grievance Handling System for a Private Company), sections 2 and 3. Contact details, pricing, evaluation and contract terms are deliberately left out of this repo.

This file is the source for requirement wording. If anything here conflicts with `docs/implementation-plan.md`, this file wins.

## From section 2: what the system is

The system needs to be purpose-built web application with two parts:

- a public side, where anyone can submit a grievance (named or anonymous) and later check its status with a reference number and PIN
- an admin side, where a few trained staff (about 3–5 users) manage cases, add notes, reply to complainants and export data

Technical guidance

- Use a common, well-supported web framework so that another developer can maintain it later.
- It must run on ordinary shared or low-cost hosting with a database (e.g. MySQL/PostgreSQL), preferably the company's existing hosting.
- Link the system from the company's website, e.g. on a subdomain such as [grievance.companydomain] or a page with a button.
- Keep public pages light and simple, with no heavy scripts, so they load fast on low-cost phones.
- Use free, open-source libraries wherever possible.

## Section 3: Requirements

All items are Mandatory (M) unless marked Desirable (D). In the quotation, confirm each ID and note anything you would do differently.

### 3.1 Public side

| ID | Requirement | M/D |
| --- | --- | --- |
| P1 | Grievance form, mobile-first, opened from the company's website; no account needed | M |
| P2 | Nepali and English, with a language switch | M |
| P3 | Short notice above the form on confidentiality, no retaliation and what happens next | M |
| P4 | Choice to submit anonymously or with a name; if named: name, phone and/or email, preferred contact method | M |
| P5 | Fields: category, location, date of incident, whether it happened once, repeatedly or is ongoing, description, one optional attachment (photo or PDF, up to 5 MB) | M |
| P6 | Optional fields, each with "prefer not to say": complainant type, sex, age group, ethnicity, disability status | M |
| P7 | On submission, show a reference number (e.g. GHM-2026-0001) and a random PIN, with advice to note them down; email them too if an email was given | M |
| P8 | Status page: enter reference number and PIN to see current status and any replies from the company, and add more information or a reply | M |
| P9 | Spam protection on the form | M |
| P10 | QR code to the form for posters | D |

### 3.2 Admin side

| ID | Requirement | M/D |
| --- | --- | --- |
| A1 | Secure login for admin users, with three roles: Administrator (all cases and settings), Focal person (all non-restricted cases), Restricted-case officer (restricted cases) | M |
| A2 | Case list with search by reference number and filters for status, category, location and date; shows days open, and highlights cases open more than 15 working days | M |
| A3 | Case page: view the submission; set status and priority; assign to a user; add internal notes (dated, with author, never shown to the complainant); send a reply that the complainant sees on the status page; upload documents | M |
| A4 | Statuses: Received, Under assessment, Under investigation, Referred / Out of scope, Resolved, Closed, Reopened. Priorities: Low, Medium, High, Critical | M |
| A5 | Restricted cases: any case in a restricted category (e.g. sexual harassment or abuse), or marked restricted by a user, is visible only to Restricted-case officers and the Administrator | M |
| A6 | Staff can enter grievances received offline (phone, email, drop box, in person), with a "channel" field | M |
| A7 | Simple settings page where the Administrator can manage users, categories (and which are restricted) and locations, without a developer | M |

### 3.3 Notifications and reports

| ID | Requirement | M/D |
| --- | --- | --- |
| N1 | Email alert to focal persons for each new case and each new complainant message, with no grievance details (only "log in to view"). For restricted cases, the alert goes only to Restricted-case officers | M |
| N2 | Email acknowledgement to the complainant, if they gave an email | M |
| N3 | Summary page: number of cases by status and by category, for a chosen date range (a simple table or a chart) | M |
| N4 | Export to Excel/CSV of cases in a date range: reference, dates received and closed, channel, category, location, priority, status, complainant type, sex, age group, ethnicity and disability status. Names and contact details are left out unless the Administrator chooses to include them | M |

### 3.4 Security and data

| ID | Requirement | M/D |
| --- | --- | --- |
| S1 | HTTPS on all pages | M |
| S2 | Passwords stored hashed; account locked for 15 minutes after 5 failed logins; automatic logout after 30 minutes idle | M |
| S3 | Sensitive fields (name, contact details, description, messages) encrypted in the database, using the framework's built-in encryption | M |
| S4 | Uploaded files stored outside the public web folder, and viewable only by logged-in users with access to that case | M |
| S5 | No IP address or device data stored for anonymous submissions | M |
| S6 | Activity log: who changed status, added notes, viewed or exported each case, and when | M |
| S7 | Automatic daily database backup, kept for at least 14 days, with a short restore instruction | M |
| S8 | Vendor keeps all data confidential, accesses live data only with the company's permission, and reports any suspected breach to NBI and the company within 24 hours, in line with Nepal's Individual Privacy Act, 2075 | M |
| S9 | Basic accessibility on public pages: labelled fields, readable contrast, works with a screen reader and keyboard | M |
| S10 | Two-factor login for admin users | D |

## Not from the RFQ: draft Nepali labels for A4

The RFQ gives A4's values in English only. The Nepali labels below are drafts to be confirmed with NBI at kick-off. Keep the enum keys in English, put the Nepali labels in `lang/ne.json`, and mark them "pending kick-off confirmation" in `docs/requirements-traceability.md`, so a wording change later needs no code change.

| Enum key | English (RFQ wording) | Nepali (draft) |
| --- | --- | --- |
| `received` | Received | प्राप्त |
| `under_assessment` | Under assessment | मूल्याङ्कनमा |
| `under_investigation` | Under investigation | छानबिनमा |
| `referred_out_of_scope` | Referred / Out of scope | अन्यत्र पठाइएको / दायराबाहिर |
| `resolved` | Resolved | समाधान भएको |
| `closed` | Closed | बन्द |
| `reopened` | Reopened | पुनः खोलिएको |
| `low` | Low | न्यून |
| `medium` | Medium | मध्यम |
| `high` | High | उच्च |
| `critical` | Critical | अति गम्भीर |
