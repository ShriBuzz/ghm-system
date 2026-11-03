---
target: public grievance pages (form, received, status lookup, status)
total_score: 28
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 4
target_identity: "file:/Users/shrijanbajracharya/Development/ghm-system/resources/views/public/form.blade.php"
target_fingerprint: "sha256:c6e1210f83fc87fceddca21638b17f84682827420af41e54b0348b785752717d"
target_path: /Users/shrijanbajracharya/Development/ghm-system/resources/views/public/form.blade.php
timestamp: 2026-11-02T19-02-05Z
slug: resources-views-public-form-blade-php
---
Method: dual-agent (A: design review · B: detector and browser), isolated.

## Design health score
| # | Heuristic | Score | Key issue |
|---|---|---|---|
| 1 | Visibility of system status | 3 | Status label without meaning; Done ends on a blank form |
| 2 | Match system / real world | 3 | Nepali "Close" (बन्द गर्नुहोस्) collides with status "Closed" (बन्द); "connection" jargon |
| 3 | User control and freedom | 3 | Status reply lost on language switch |
| 4 | Consistency and standards | 3 | Full vs auto width buttons; first section has no heading; mixed numerals |
| 5 | Error prevention | 2 | Reference/PIN not normalised, misses count to the lock; no double-submit guard |
| 6 | Recognition rather than recall | 3 | Status page shows no category or month |
| 7 | Flexibility and efficiency | 3 | Adequate for audience |
| 8 | Aesthetic and minimalist | 3 | Calm; some redundant hints |
| 9 | Error recovery | 3 | Refusals filed under "Some answers need fixing" |
| 10 | Help and documentation | 2 | No status meanings; no lost-PIN route |
| Total | | 28/40 | Good |

## Design specificity verdict
Behaviour and copy are product-specific (anonymous first, location-stripping hint, PIN card, shared-phone hints, "Grievance team"). Visual grammar is a deliberately plain government-form look per the brief; the emotional moments share the grammar of every field; no owner identity yet (open question).
Deterministic scan: CLI 0 findings (regex-only on .php, undercount). Browser: body-text-viewport-edge on /ne (real; root cause the file input, issue 1); flat-type-hierarchy advisory at 360 px (h1 26.2, h2 22, body 18; 1.22 vs 1.25); straggler on #attachment-hint (/ne) and #close-hint (status case). Lookup and /en at 1280 clean. Received page not reached by either assessment: the 10-an-hour limit for this address was already used.

## Priority issues
1. [P1] Form scrolls sideways at 320 px: native file input min width (~333 px) stretches the grid column of form[data-grievance] and .section (349 px at 320; 5 px into the gutter at 360). Fix: grid-template-columns: minmax(0,1fr); file input min-width: 0; Playwright check at 320. /impeccable adapt
2. [P1] "Close" reads as closing the grievance (track.close, track.closed, status.closed all बन्द; track.expired "your grievance closes"). Fix: "Finish and hide" style wording, say the grievance stays open; Nepali native read. /impeccable clarify
3. [P1] Limiter, spam and session refusals shown as field errors under "Some answers need fixing" with a link to Send. Fix: form key first under "Your grievance was not sent", no link, concrete time, plain words. /impeccable harden
4. [P1] Reference/PIN not normalised (Devanagari digits, spaces, missing hyphens) and each miss counts to the 5-try lock. Fix: normalise before lookup, Pest tests. /impeccable harden
5. [P2] Double tap on Send can create two cases; first PIN never shown. Fix: disable Send after submit in form.js. /impeccable harden

## Persona red flags
- Jordan: believes बन्द भयो closed the case; blank form after Done may cause a resend; date hint before "How often?".
- Sam: role=status message present on load may not announce; anonymous question has no heading; focus ring same teal as selected border; 320 px sideways scroll.
- Casey: double-tap duplicate; status reply lost on language switch; hyphenated reference on symbol keyboard.
- Nepali factory worker from the poster: no company name; file input in browser language; "Who are you?"/"Ethnic group" right after the anonymity promise; shared Wi-Fi refusal worded as "this connection".

## Minor observations
- Status meanings and category/month on the status page (wording from NBI); no last-updated date.
- Year select defaults to current year with no "Choose" (January risk).
- Day select cuts off छान्नुहोस् at 360 px.
- Flat type steps at 360 px; text-wrap: pretty on p for stragglers.
- noscript hint lacks bottom margin; recurrence error does not mark the cards; lookup error heading reads oddly.
- PIN could be grouped; redundant "only if you give your name"; draft notice must be impossible to ship.

## Questions to consider
- Optional demographic questions after sending instead of after the anonymity promise (check RFQ P6)?
- What should the last screen say, including a lost PIN?
- Should the restricted category say who reads it?
- What tells a worker this is the company's real channel?
