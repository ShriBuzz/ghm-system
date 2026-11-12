---
target: public pages after the whole of WP4
total_score: 28
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 3
target_identity: "file:/Users/shrijanbajracharya/Development/ghm-system/resources/views/public/form.blade.php"
target_fingerprint: "sha256:74c282432b468b8cb26bc73879b540f3591352f9001c396264f42bbdc54abfed"
target_path: /Users/shrijanbajracharya/Development/ghm-system/resources/views/public/form.blade.php
timestamp: 2026-11-12T15-39-52Z
slug: resources-views-public-form-blade-php
---
Method: dual-agent (A: design review · B: detector and browser evidence; isolated, parallel)

# Critique: public pages after the whole of WP4 (lists, BS date, notice, emergency line, other ways, received and status pages)

## Design Health Score
| # | Heuristic | Score | Key issue |
|---|---|---|---|
| 1 | Visibility of system status | 3 | Done lands on an empty form with no confirmation; bare status word on the status page |
| 2 | Match with the real world | 3 | Latin digits in BS selects, AD date on the Nepali status page, English file button |
| 3 | User control and freedom | 3 | Anonymous by default, draft kept across language switch, Finish and hide and Done |
| 4 | Consistency and standards | 3 | Error summary placed differently on lookup; primary button full width at desktop; 21 px inline links |
| 5 | Error prevention | 2 | Contact preference after the fields it governs; year preselected; day 32 only server-checked |
| 6 | Recognition rather than recall | 3 | Short lists hidden in selects; restricted-category promise far from the category choice |
| 7 | Flexibility and efficiency | 3 | Autocomplete, inputmode, case-insensitive reference |
| 8 | Aesthetic and minimalist design | 3 | Notice 1,013 px at 360 px (NE), two paragraphs repeated on the received page |
| 9 | Recognise and recover from errors | 3 | Summary links scroll the question and its error out of view; some errors repeat the label |
| 10 | Help and documentation | 2 | No lost-PIN path; statuses unexplained; Other ways last and without a number yet |
| Total | | 28/40 | Good |

## Design specificity verdict
LLM: behaviour is authored for this product (keep-these card, shared-phone endings on Done and Finish and hide, anonymous first, BS date, calm tel: emergency line); the visual style is a deliberately generic plain form. Specificity breaks in Nepali-first details: Latin digits in BS selects, an AD date on the Nepali status page, an English file button, and "गते याद छैन" cut to "गते याद".
Detector: source scan 0 findings; URL scans of /ne, /en, /ne/status, /en/status at 360 and 1280 px: 0 failures, 4 advisory stragglers (notice points, NE attachment hint). axe: 0 violations and 0 incomplete on 26 runs (13 page states x 2 widths). No horizontal overflow anywhere. tel:100 link 328x59 at 360 px, first focus stop on every page.
Overlay: blocked by the pages' CSP (default-src 'self') on every page tried; not bypassed; no overlay visible.

## Priority issues
1. [P1] Error-summary links scroll the control flush to the top, hiding its question, hint and error (category, date, how often, description); date selects carry aria-invalid but no aria-describedby. Fix: scroll-margin-top on form controls large enough for label, hint and error; aria-describedby on each date select. harden.
2. [P1] The day select is ~97 px at 360 px: "गते याद छैन" shows as "गते याद" (reads as the opposite), "गते य" at 320 px; English "I don't". Fix: below 40rem, day on its own row (or each part on its own row); update DESIGN.md's one-row rule. adapt.
3. [P1] Our About-you hint ("help the company see who raises grievances") reads as identifying complainants, right after the anonymity promise. Fix: truthful wording (staff handling the case see them; reports show totals only, groups under 5 as "<5"; skip any question), Nepali signed off. clarify.
4. [P2] "Do not contact me" then "give a phone or an email" with no reason; the preference question sits after the fields. Fix: preference first in the contact group; a CSS-only hint when Do not contact me is chosen; its own error message. clarify.
5. [P2] Nepali-first slips: Latin digits in BS day and year options, AD date on the Nepali status page (a question for the lead), native English file button. Fix: Devanagari digits in option text with ASCII values; ask about BS on the status page; a styled label for the file input with the native input as fallback. adapt.

## Persona red flags
Jordan: two screens before the first question; told to give a phone after Do not contact me; error links land on a field with no visible question; Done drops them on an empty form. Sam: date and radio errors only via the fieldset's aria-describedby; focus ring only on the 22 px radio, not the card. Casey: Send at ~3,661 px of 4,001; 21 px links; truncated day select. Frightened worker: ~1,000 px of Devanagari first; "गते याद" reversed; Latin "2083" and "Choose File"; About-you hint sounds like surveillance; no reassurance on choosing a harassment category; the phone route last and without a number.

## Minor observations
Whole date group turns red including the valid year; description error repeats its label; lookup error breaks the reference at a hyphen; empty-thread hint sits 0.5rem above the reply label; primary button full width at desktop vs DESIGN.md; recurrence radios lack aria-invalid on error; no lost-PIN route; text-wrap: pretty would fix the stragglers.

## Questions to consider
1. Should About you come after the grievance is received, as optional questions on the received page?
2. Should "I don't remember the day" be its own visible choice instead of an option in a narrow select?
3. Should picking a restricted category bring back the company's fourth notice point, CSS-only, without changing its words?
