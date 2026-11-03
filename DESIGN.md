---
name: GHM System public pages
description: The public side of the online grievance handling system; a calm, plain, bilingual form for filing and following a grievance from any phone.
colors:
  accent: "#0a5c70"
  accent-strong: "#07465a"
  accent-wash: "#e3eef1"
  paper: "#ffffff"
  band: "#f2f6f7"
  ink: "#1b2428"
  ink-soft: "#4b575d"
  line: "#c9d3d7"
  error: "#b42318"
typography:
  headline:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", "Noto Sans Devanagari", "Nirmala UI", "Kohinoor Devanagari", sans-serif'
    fontSize: "clamp(1.875rem, 1.5rem + 1.5vw, 2.25rem)"
    fontWeight: 700
    lineHeight: 1.35
  title:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", "Noto Sans Devanagari", "Nirmala UI", "Kohinoor Devanagari", sans-serif'
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.35
  body:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", "Noto Sans Devanagari", "Nirmala UI", "Kohinoor Devanagari", sans-serif'
    fontSize: "1.125rem"
    fontWeight: 400
    lineHeight: 1.65
  label:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", "Noto Sans Devanagari", "Nirmala UI", "Kohinoor Devanagari", sans-serif'
    fontSize: "1.125rem"
    fontWeight: 600
    lineHeight: 1.65
  hint:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", "Noto Sans Devanagari", "Nirmala UI", "Kohinoor Devanagari", sans-serif'
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.65
  code-display:
    fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace'
    fontSize: "clamp(1.75rem, 1.4rem + 2vw, 2.5rem)"
    fontWeight: 600
    letterSpacing: "0.06em"
    fontFeature: '"tnum"'
  code-inline:
    fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace'
    fontSize: "1.125rem"
    letterSpacing: "0.04em"
    fontFeature: '"tnum"'
rounded:
  sm: "0.375rem"
  md: "0.5rem"
  lg: "0.75rem"
spacing:
  2xs: "0.25rem"
  xs: "0.5rem"
  sm: "0.75rem"
  md: "1rem"
  panel: "1.25rem"
  group: "1.5rem"
  lg: "2rem"
  section: "2.5rem"
  xl: "3rem"
  target: "2.75rem"
  gutter: "clamp(1rem, 4vw, 2rem)"
  measure: "40rem"
components:
  button-primary:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.paper}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    padding: "0.75rem 1.5rem"
    height: "3rem"
  button-primary-hover:
    backgroundColor: "{colors.accent-strong}"
    textColor: "{colors.paper}"
  button-secondary:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.accent}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    padding: "0.75rem 1.5rem"
    height: "3rem"
  button-secondary-hover:
    backgroundColor: "{colors.accent-wash}"
    textColor: "{colors.accent-strong}"
  input-text:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.sm}"
    padding: "0.5rem 0.75rem"
    height: "{spacing.target}"
  textarea:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.sm}"
    padding: "0.5rem 0.75rem"
    height: "10rem"
  choice-card:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    padding: "0.625rem 0.875rem"
    height: "{spacing.target}"
  choice-card-selected:
    backgroundColor: "{colors.accent-wash}"
    textColor: "{colors.ink}"
  language-switch-link:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.accent}"
    rounded: "{rounded.sm}"
    padding: "0.25rem 0.875rem"
    height: "{spacing.target}"
    width: "{spacing.target}"
  language-switch-link-hover:
    backgroundColor: "{colors.accent-wash}"
  language-switch-current:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.paper}"
  site-header:
    backgroundColor: "{colors.band}"
    textColor: "{colors.ink}"
    padding: "0.5rem"
  notice-band:
    backgroundColor: "{colors.band}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "{spacing.panel}"
  keep-card:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.lg}"
    padding: "{spacing.panel}"
  status-facts:
    backgroundColor: "{colors.band}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "{spacing.panel}"
  notice-line:
    backgroundColor: "{colors.accent-wash}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "0.75rem 1rem"
  error-summary:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "{spacing.panel}"
  message-team:
    backgroundColor: "{colors.band}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "0.875rem 1rem"
    width: "90%"
  message-mine:
    backgroundColor: "{colors.accent-wash}"
    textColor: "{colors.ink}"
---

# Design System: GHM System public pages

## Overview

**Creative North Star: "The Quiet Counter"**

The public pages behave like a calm service counter: plain, private, and with nothing on it that distracts from the person in front of it. The visitor's own words come first. Everything else (the notice, the questions, the reference number and PIN, the replies) is laid out in one column of white paper with a pale band behind the header and the notice, one deep teal for anything that can be acted on, and the device's own system font in Nepali or English.

Density is low on purpose. Body text is 18 px, every target is at least 44 px, sections stand apart by 2.5rem and a hairline rule, and there is no decoration: no shadows, no motion, no icons, no illustration. The page is light only. The few moments that carry weight (the "keep these" card, the error summary, the message thread) are marked by heavier borders and a tinted wash, never by ornament.

The system is bound by durable product constraints, not by taste alone: no web fonts, no build step, no third-party requests, every form works with JavaScript off, every layout holds at 360 px in both scripts, WCAG 2.2 AA, and a self-only CSP that keeps every style in the one stylesheet. The accent is a placeholder until the client's company colour arrives at kick-off; its roles are fixed, its hue is not.

**Key Characteristics:**
- One column, 40rem measure, white paper, a pale cool band for quiet grouping.
- One accent, Placeholder Deep Teal, for every actionable or chosen thing and nothing else.
- System font stack with Devanagari fallbacks; monospace only for the reference number and PIN.
- Sturdy and obvious controls: 44 px and up, 2 px borders, whole-row choice cards.
- Flat and still: no box-shadows, no transitions, no animations.
- Meaning is never carried by colour alone: words, borders, position and the native control repeat it.

## Colors

A near-monochrome cool-grey paper palette with a single deep teal accent and one red reserved for errors.

### Primary
- **Placeholder Deep Teal** (#0a5c70): the one accent. Links, primary buttons (fill) and secondary buttons (border and text), the file picker's button, the selected choice card's border, the 3 px focus ring, the language switch (frame, links, filled current language), the "keep these" card's 3 px border, the status notice line's border, and the border of the visitor's own messages. Also the browser's `accent-color` and `caret-color`, so native radios and the text caret match. 7.56:1 on white; white on it is 7.56:1.
- **Pressed Deep Teal** (#07465a): the hover state of the accent: primary button fill, secondary button border and text. Only ever a darker step of the accent, never a second hue.
- **Teal Wash** (#e3eef1): the tinted ground of a chosen or "mine" thing: the selected choice card, the visitor's own messages, the status notice line, the secondary button and language link on hover, and text selection. Soft ink on it is 6.30:1.

### Neutral
- **Paper White** (#ffffff): the page, inputs, buttons at rest, and the "keep these" card. Text on the accent.
- **Counter Band** (#f2f6f7): the pale cool ground that groups without a border: the site header, the company's notice, the status facts and the team's messages. Soft ink on it is 6.84:1.
- **Slate Ink** (#1b2428): all body text, headings, labels and the site name. 15.79:1 on white.
- **Soft Ink** (#4b575d): hints, definition terms (the labels above reference, PIN, status, date), message meta, and the 2 px input borders. 7.45:1 on white.
- **Quiet Line** (#c9d3d7): 1 px dividers between sections and under the header, the contact group's frame, the team message frame, and the resting border of a choice card. 1.52:1 on white, so it is decorative only; it never carries the boundary of a control on its own.

### Error
- **Signal Red** (#b42318): error text, the error-summary heading, links and 3 px frame, and the border of a field in error. 6.57:1 on white. Always paired with words and a visually hidden "Error:" prefix.

### Named Rules
**The One Accent Rule.** There is one accent and it means "you can act on this" or "this is the one you chose". Pressed Deep Teal and Teal Wash are its states, not new colours. Nothing decorative is ever teal.

**The Seven to One Rule.** Placeholder Deep Teal stands in until the client's company colour arrives at kick-off. Any colour that replaces it must keep at least 7:1 against white (the placeholder is 7.56:1) and take over every one of its roles: links, primary buttons, the selected choice card, focus rings, the language switch, and the "keep these" card border. Pressed Deep Teal and Teal Wash are replaced with it, as its darker step and its pale wash.

**The More Than Colour Rule.** A state is never told by colour alone. A chosen card has a teal border, a wash and the checked native radio; an error has words, a red border and a summary that links to the field; "You" and "Grievance team" are written on each message, and position only repeats it.

## Typography

**Display Font:** none; there is no separate display face.
**Body Font:** the system UI stack (with Noto Sans Devanagari, Nirmala UI and Kohinoor Devanagari as Devanagari fallbacks, then sans-serif)
**Label/Mono Font:** the system monospace stack, for the reference number and PIN only

**Character:** One honest system face in whatever form the visitor's phone already has, so Nepali and English render natively and nothing is downloaded. Hierarchy comes from size and weight steps, not from a second family.

### Hierarchy
- **Headline** (700, clamp(1.875rem, 1.5rem + 1.5vw, 2.25rem), 1.35): the page's one h1, balanced wrap; 30 px at 360 px wide, 36 px on desktop.
- **Title** (700, 1.5rem, 1.35): section headings (h2), balanced wrap. Inside the notice the h2 drops to body size (1.125rem), and the error summary's h2 is 1.25rem in Signal Red.
- **Body** (400, 1.125rem, 1.65): all running text and input text; paragraphs capped at 65ch.
- **Label** (600, 1.125rem, 1.65): field labels, legends, choice card text, buttons, the site name, status fact values. The status value itself steps up to 1.375rem.
- **Hint** (400, 1rem, 1.65): hints under labels, definition terms, message meta, the date-part labels and the language switch (600 there).
- **Code display** (monospace 600, clamp(1.75rem, 1.4rem + 2vw, 2.5rem), letter-spacing 0.06em, tabular figures): the reference number and PIN on the "keep these" card; wraps anywhere rather than overflow.
- **Code inline** (monospace, body size, letter-spacing 0.04em, tabular figures): the reference number in the status facts.

### Named Rules
**The No Web Fonts Rule.** The system stack is a product constraint, not a placeholder: no font is ever downloaded, so Devanagari renders in the device's own font.

**The Data Not Costume Rule.** Monospace is for the reference number and PIN, characters a person must copy exactly. It is never used for headings, labels or flavour.

**The Quarter Step Rule.** Each step from body to h2 to h1 is at least 1.25 times the one below, at phone width too.

## Layout

One column, centred, at a 40rem measure. The header and main share the same inline padding, `max(gutter, (100% - measure) / 2)`, so the site name and language switch line up with the content edge on wide screens; the gutter is clamp(1rem, 4vw, 2rem). Main has 2rem above and 3rem below.

The rhythm is "more room between groups than inside them". The form's sections sit 2.5rem apart, each opened by a 1 px Quiet Line rule and 2rem of top padding; inside a section, questions sit 1.5rem apart; inside a question, label, hint, error and control sit 0.5rem apart. Panels (notice, contact group, error summary, "keep these" card, status facts) all take 1.25rem padding. The status pages use the same 1.5rem stack.

There is one breakpoint, 40rem. Below it the primary button is full width; at and above it the three status facts sit in one row spread across the measure. The day, month and year selects share one row at every width, the year keeping the width its four digits need. Nothing scrolls sideways at 320 px; the file input's minimum width is released for that reason.

## Elevation & Depth

Flat. There are no box-shadows anywhere. Depth is told by three things only: the Counter Band background (header, notice, status facts, team messages), 1 px Quiet Line dividers between sections and under the header, and borders whose weight rises with importance (1 px for grouping, 2 px for controls and the notice line, 3 px for the "keep these" card and the error summary). There is no motion: no transitions and no animations; hover changes are instant.

### Named Rules
**The Flat Counter Rule.** Nothing floats. A surface is set apart by the band, a rule or a heavier border, never by a shadow.

**The Still Page Rule.** No transitions, no animations. A state change is immediate.

**The Light Only Rule.** The pages declare `color-scheme: light` and have no dark theme. This is the current deliberate state, not a promise that one will never be added; a dark theme would need its own contrast pass against the Seven to One Rule.

## Shapes

Gently rounded rectangles throughout, in three steps. Small (0.375rem) for inputs, selects, the textarea, the file picker's button and the language switch's inner pills. Medium (0.5rem) for buttons, choice cards, the notice, the contact group, the error summary, the notice line, the status facts, the messages and the language switch's frame. Large (0.75rem) only for the "keep these" card, the one container that holds what the visitor must take away. Borders are solid; there are no dashed or dotted lines, no clipped corners and no decorative silhouettes.

## Components

**Sturdy and obvious.** Every control is large, bordered and plainly what it is.

### Buttons
- **Shape:** medium radius (0.5rem), 2 px border, at least 3rem tall, 0.75rem by 1.5rem padding, label weight (600).
- **Primary:** Placeholder Deep Teal fill with white text and a teal border. One per form, for its main action (Send, Check, Send a message). Full width below 40rem.
- **Hover / Focus:** primary fill steps to Pressed Deep Teal. Focus is the global 3 px teal outline with a 2 px offset. While a submission is on its way its label changes to the sending text, and a second tap within 10 seconds does nothing (JavaScript only). It is never marked disabled, since it works again after that.
- **Secondary:** white with a teal border and teal text; on hover the wash ground with Pressed Deep Teal border and text. Used for the ending actions (Done, Close), placed under a hint that says what they do.

### Chips (choice cards)
- **Style:** each radio option is a whole-row card: 2 px Quiet Line border, medium radius, 0.625rem by 0.875rem padding, at least 44 px tall, label weight, a 1.375rem native radio at the leading edge, and an optional hint below the label.
- **State:** chosen means teal border plus Teal Wash ground plus the checked radio. The whole row is the click target.

### Cards / Containers
- **Corner Style:** medium (0.5rem); large (0.75rem) for the "keep these" card.
- **Background:** Counter Band for the notice and status facts; Paper White for the contact group, error summary and "keep these" card.
- **Shadow Strategy:** none (see Elevation & Depth).
- **Border:** none on band panels; 1 px Quiet Line on the contact group; 3 px Signal Red on the error summary; 3 px Placeholder Deep Teal on the "keep these" card.
- **Internal Padding:** 1.25rem.

### Inputs / Fields
- **Style:** full width, at least 44 px tall (textarea 10rem, resizable vertically), 0.5rem by 0.75rem padding, 2 px Soft Ink border, small radius, white ground, body text. The file picker's button is styled as a secondary button at small radius.
- **Focus:** the global 3 px teal outline at a 2 px offset; the border does not change.
- **Error:** the border turns Signal Red, and an error line in Signal Red at label weight sits between the hint and the control, starting with a visually hidden "Error:".

### Navigation
- **Site header:** Counter Band strip with a 1 px Quiet Line under it; the site name in Slate Ink at label weight (underlined on hover) at one end and the language switch at the other, wrapping on narrow screens.
- **Language switch:** both languages side by side in a 1 px teal frame with medium radius, like a two-way toggle. The current language is a filled teal pill with white text; the other is a teal link that takes the wash on hover. Each pill is at least 44 by 44 px, hint size at weight 600, each labelled in its own language.

### The "keep these" card (signature)
The received page's framed card: 3 px teal border, large radius, 1.25rem padding. Soft Ink terms ("Reference number", "PIN") over the values in code display monospace, large and letter-spaced for copying, then one line of advice.

### Error summary (signature)
At the top of a refused form: 3 px Signal Red frame, medium radius, a red heading, and a list of red links at label weight, each jumping to its field. A refusal about the sending (rather than an answer) gets its own heading and no links. With JavaScript on, it takes focus on load.

### Message thread (signature)
An ordered list of messages 1rem apart, each at most 90% wide with medium radius and 0.875rem by 1rem padding. Team messages: Counter Band ground, 1 px Quiet Line border, at the start. The visitor's own: Teal Wash ground, teal border, at the end. Each says who wrote it in bold Slate Ink, followed by the date in Soft Ink.

### Status facts and notice line
The status page's reference, status and received date as a band panel of term and value pairs, stacked on phones and in one row from 40rem. A one-line outcome message (the notice line) is a teal-bordered wash strip at medium radius that takes focus on load.

## Do's and Don'ts

### Do:
- **Do** take every colour from the stylesheet's variables, so the company colour can replace the accent in one place.
- **Do** keep any replacement accent at 7:1 or more against white and give it all of the accent's roles, with its darker step and its wash replaced alongside it.
- **Do** make every target at least 44 px (2.75rem) in both directions, and make a radio option the whole row.
- **Do** mark a chosen or wrong state with words or a border as well as colour.
- **Do** use the 3 px teal outline with a 2 px offset for keyboard focus on everything that can take it.
- **Do** separate form sections with 2.5rem, a 1 px Quiet Line rule and 2rem of top padding, with 1.5rem between questions inside them.
- **Do** keep every public style in the one stylesheet; the self-only CSP forbids inline styles and scripts.
- **Do** check every layout at 360 px and 320 px in Nepali and English, with JavaScript off as well as on.
- **Do** keep print views and emails in the same plain voice: the system font, bilingual text, monospace only for an address or code someone must copy, and nothing loaded from another site.

### Don't:
- **Don't** add web fonts, icon fonts, glyph icons or illustrations.
- **Don't** add box-shadows, transitions or animations.
- **Don't** use the accent for decoration, or introduce a second accent hue.
- **Don't** use monospace for anything except a reference number, PIN or address meant to be copied.
- **Don't** let the 1 px Quiet Line be the only thing that shows where a control is; it is 1.52:1 and decorative.
- **Don't** set the company's notice apart with a coloured edge; it sits on the band.
- **Don't** add a dark theme without its own contrast pass.
- **Don't** apply this system to the Filament admin panel; it keeps Filament's own UI.
