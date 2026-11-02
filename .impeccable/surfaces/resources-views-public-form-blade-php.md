---
version: 1
slug: "resources-views-public-form-blade-php"
primary_target: "resources/views/public/form.blade.php"
related_targets: ["resources/views/public/received.blade.php","resources/views/public/status.blade.php","resources/views/public/status-lookup.blade.php"]
---

## Scope and mode

The public grievance pages (WP1): the form at `/{locale}`, the received page, the status lookup and the status page. Operate mode: the visitor completes a task. Extends the WP0 shell's world (no new world); confirmed in `/impeccable shape` on 2026-11-02.

## Audience and job

Factory workers and community members on low-cost Android phones, Nepali first, some with low literacy, some upset or afraid, arriving from the company site button or a QR poster. Job: send a grievance in one sitting and leave with the reference and PIN written down; later look it up, read replies, add information. Nothing typed is lost to an error, an expired session or a language switch.

## Direction (extension of the incumbent shell)

Plain-paper-form clarity: one column, generous spacing, 44 px targets, plain words, no decoration. White page, pale header band, one teal accent, system fonts with Devanagari fallbacks, 18 px base; colours stay variables so the company's colour replaces the accent at kick-off.

Memorable moments: (1) the notice, then "stay anonymous" first, so reassurance comes before effort; (2) the received page's framed "keep these" card with the reference and PIN at large size, characters spaced for copying; (3) the status thread, "Grievance team" replies and "You" messages told apart by label and position, never colour alone.

## Structure

Form order: notice; "Your name?" (stay anonymous / give my name, contact fields shown only for a name, by CSS); "What happened" (category, location, when as day/month/year with "I don't remember the day", how often, description, photo or PDF); "About you (optional)" as five selects, each blank by default with "prefer not to say"; full-width Send. A quiet "Already sent a grievance? Check it" link near the top. Received page ends with Done; status page has Close.

## States and ranges

Notice 1–4 short paragraphs; 5–12 categories, 3–10 locations; description up to 5,000 characters. Error summary at the top plus a message by each field; file too large; session expired with the text restored; spam refusal, plain, text kept. Reference like GHM-2026-0001, 6-digit PIN. Seven RFQ statuses; thread from "No replies yet" to long; lock after 5 wrong PINs; expired access returns to the lookup.

## Anti-goals and open decisions

No wizard, no decorative icons or illustrations, no web fonts, no colour-only meaning, no marketing tone. Never invent the company name, response-time promises or contact details. P6 options and the AD-only date are placeholders until NBI answers (questions 5 and 8).
