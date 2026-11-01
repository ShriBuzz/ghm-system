# Production access log (S8)

RFQ S8: the vendor accesses live data only with the company's permission. Every time anyone from the vendor signs in to the production server, the production database or the live staff panel, they need the company's written permission for that occasion, and they add a row here.

The live site's staff accounts used by the company's own staff are not recorded here; this log covers the vendor only.

## Rules

- Ask in writing (email is enough) before each access, and say why. Permission for one occasion does not cover the next.
- Look at the least data the task needs. Never copy personal data off the server, and never use live data for testing.
- The only automated access to production is the read-only `@smoke` Playwright suite, which needs no row here.
- If anything unexpected is seen during the access, follow `docs/breach-report-template.md`.

## Log

Fill in one row per access. Write who gave permission by role, and keep the permission email.

| Date | Vendor person | Permission from (role), date of the email | Reason | What was accessed | Start and end time (Nepal time) | Data copied off the server? | Notes |
|---|---|---|---|---|---|---|---|
| | | | | | | No | |
