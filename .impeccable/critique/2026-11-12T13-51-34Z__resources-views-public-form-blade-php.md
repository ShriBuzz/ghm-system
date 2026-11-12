---
target: "public pages after WP4 step 4 (U10): emergency line, notice, other ways, received page, status title"
total_score: 27
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 2
target_identity: "file:/Users/shrijanbajracharya/Development/ghm-system/resources/views/public/form.blade.php"
target_fingerprint: "sha256:74c282432b468b8cb26bc73879b540f3591352f9001c396264f42bbdc54abfed"
target_path: /Users/shrijanbajracharya/Development/ghm-system/resources/views/public/form.blade.php
timestamp: 2026-11-12T13-51-34Z
slug: resources-views-public-form-blade-php
---
Method: dual-agent (A: design review · B: detector and browser evidence; isolated, parallel)

# Critique: public pages after WP4 step 4 (U10, the company's public text)

## Design Health Score

| # | Heuristic | Score | Key issue |
|---|---|---|---|
| 1 | Visibility of system status | 3 | Received page opens with "Thank you" and the reference; error summary stays above the longer notice |
| 2 | Match with the real world | 3 | "Phone, email" in Other ways has no number or address yet |
| 3 | User control and freedom | 3 | Language switch on the received page keeps the reference and PIN |
| 4 | Consistency and standards | 3 | The 100 link breaks the house 44 px target; the emergency line copies the site-name style |
| 5 | Error prevention | 3 | "The PIN cannot be shown again" ends a 5-line paragraph |
| 6 | Recognition rather than recall | 3 | Large spaced codes; what happens next repeated after sending |
| 7 | Flexibility and efficiency | 2 | Returning visitors scroll 1,300–1,500 px of notice at 360 px |
| 8 | Aesthetic and minimalist design | 2 | Notice band 993 px (NE) / 1,112 px (EN) at 360 px |
| 9 | Recognise and recover from errors | 3 | Unchanged error summary, focused, above the notice |
| 10 | Help and documentation | 2 | Other ways not actionable yet (C12) and last on a 4,000 px page |
| Total | | 27/40 | Acceptable |

## Design specificity verdict
LLM: the system is authored for this audience (Nepali first, anonymity before effort, keep-these card, shared-phone Done hint, no third-party loads). The new U10 pieces are the least specific: the emergency line copies the site name's style and reads as a tagline; the notice became a long grey bulleted box with a body-size heading; Other ways is a generic last section. No system rule broken.
Detector: source scan 0 findings; URL scans (/en, /ne, /en/status, /ne/status at 360 and 1280) 0 failures, 2 advisory stragglers per width (notice li last word alone; NE attachment hint pre-existing). axe 0 violations on 12 page/width checks; no horizontal overflow. 100 tap box 26–31 x 45 px (WCAG 2.5.8 passes, house 44 px fails).
Overlay: blocked on all 5 pages by the pages' own CSP (default-src 'self'); no overlay visible, CSP not bypassed.

## Priority issues
1. [P1] Emergency 100 link too narrow (26–31 px) and its focus ring cuts through the line above at 360 px. Fix: whole sentence as the tel link, min 44 px tall; removes the :number split. harden.
2. [P1] Notice pushes the first question 1.6–1.8 screens down at 360 px. Fix without rewording: Quiet Line rule between the promise (heading + 4 points) and the two information paragraphs; notice h2 1.125rem -> 1.25rem. Moving the two paragraphs to the received page alone conflicts with P3; that is C7 for the company. layout, typeset.
3. [P2] Emergency line reads as the site's tagline; English header 3 rows (186 px) at 360 px. Fix: own strip with a Quiet Line; tighter header gap so site name and switch share a row (~52 px saved). layout.
4. [P2] Received page: our write-it-down / photo / cannot-be-shown-again advice ends a 5-line paragraph; at 320x640 the PIN reaches the fold. Fix: two paragraphs, company sentence then our advice at label weight. clarify, adapt.
5. [P3] Other ways last on a 4,000 px page with no number or email yet (C12). Fix: tel/mailto links at 44 px when C12 arrives; possibly an in-page link near the top using the heading's words. layout.

## Persona red flags
Jordan: police line first, same weight as the site name; two screens of grey text; "Phone, email" leads nowhere. Sam: first tab stop named "100", focus ring overlaps the line above; no heading for what happens next on the received page. Casey: 26–31 px wide target at the top edge; 1,300–1,500 px scroll to the form. Frightened factory worker: ~1,000 px of dense Devanagari; the comforting heading is body size; write-down/photo advice buried mid-paragraph.

## Minor observations
Stragglers in notice points (text-wrap: pretty on p and li). Pre-existing: EN Day select shows "Choos" at 360 px; inline links 21 px tall; Latin digits in Nepali date selects. "Keep these" heading then the company's "Keep ... safe" sentence repeats, but the heading names the card. tel:100 on desktop opens an app picker.

## Questions to consider
1. What happens next and Free appear on the form and the received page: which time will a frightened worker remember?
2. Should the whole emergency sentence be the button?
3. Should Other ways go live before its channels can be reached?
