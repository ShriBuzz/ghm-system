# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Complainants (public side).** Members of the public who file a grievance with the client, usually from a phone. They file in Nepali or English, receive a reference number and PIN (P7), and come back with those to follow their case. Some grievances are sensitive enough that their category is restricted.
- **Staff (admin side).** Administrator, focal person and restricted-case officer, with the visibility rules in `CLAUDE.md`. They work in the Filament panel at `/admin`, which keeps Filament's own UI and is outside Impeccable's scope.

Impeccable covers the public pages, the emails and the print views only.

## Product Purpose

The online grievance handling system for RFQ NBI/GHM/RFQ/01. A member of the public files a grievance online, gets a reference number and PIN, and can follow the case; staff handle it against a 15-day SLA, configured with Saturday as the weekend day and a holiday calendar.

Success is every RFQ section 3 requirement (P1–P10, A1–A7, N1–N4, S1–S10) met and traced in `docs/requirements-traceability.md`. Nothing beyond section 3 is built.

## Positioning

A commissioned build against a fixed RFQ, not a market product. What sets it apart is what it refuses to collect or depend on: no IP addresses or user agents stored or logged, personal fields encrypted at rest, no third-party requests from public pages, and every public form works with JavaScript off.

## Operating Context

- Public pages default to Nepali (`ne`) with English (`en`) as fallback. A language switch sets a cookie and the page `lang` attribute (P2). Categories and A4 statuses and priorities carry labels in both languages.
- Every user-facing flow is checked at 360 px (touch) and at desktop width.
- A grievance carries a category, a location, a description, attachments (up to 5 MB) and demographic fields. The PIN length is 6.
- Sessions expire after 30 idle minutes. This applies to a complainant part-way through a form as well as to staff.
- Emails go out through a database queue run by cron (`schedule:run`), so they are not instant.

## Capabilities and Constraints

- **Public page stack.** Blade templates, one hand-written stylesheet (`public/css/public.css`) and vanilla JS (`public/js/form.js`) for progressive enhancement only. No build step, Tailwind, JS framework or web fonts. Nepali text renders in whatever Devanagari font the visitor's device has.
- **Security headers (S1).** Self-only CSP on public routes, so styles and scripts come from those two files, never inline. `frame-ancestors 'none'` and `Referrer-Policy: no-referrer`.
- **Privacy.** Personal data, IP addresses and user agents are never logged. Real grievance data never leaves production, so every mockup, fixture and screenshot uses fake data.
- **Restricted cases.** A case is restricted when its category is restricted or a staff member marks it so. Only the administrator and restricted-case officers see restricted cases.
- **Fixed vocabulary.** Statuses and priorities are exactly as RFQ A4 lists them. Terms in use: grievance, reference number, PIN, category, location, focal person, restricted case, SLA.

### Open, not to be guessed

- RFQ section 3 and `docs/implementation-plan.md` are not in the repo yet. Requirement wording beyond what `CLAUDE.md` and `WP0-kickoff.md` quote is unknown.
- The client organisation behind "NBI" and the name shown on public pages and emails.
- Whether a complainant can file anonymously.
- What a complainant sees when tracking a case, beyond its status.
- Who receives which emails (N1–N4), and what the print views are for.

## Evidence on Hand

No logo, brand guide, client name, category list, location list or real content is in the repo. Do not invent testimonials, statistics, case counts or contact details.

## Product Principles

1. **The RFQ is the scope.** Build what section 3 asks for. Open questions go to the project lead, not into guesses.
2. **Privacy over convenience.** Collect and show the least personal data the task needs. No design effect justifies loosening a privacy or security rule.
3. **Works on any phone.** Every public form works with JavaScript off, at 360 px, with nothing loaded from anywhere else.
4. **Nepali first, English equal.** Every public string exists in both languages, and every layout holds in both scripts.

## Accessibility & Inclusion

- WCAG 2.2 AA for public pages, emails and print views. Chosen during init; check it against the RFQ text once that is in the repo.
- Accessibility is never trimmed for brevity. Playwright specs include axe-core checks.
- Each page declares its language in `lang`, so screen readers switch voice between Nepali and English.
