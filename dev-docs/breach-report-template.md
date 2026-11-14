# Suspected data breach: report to NBI and the company (S8)

RFQ S8: the vendor reports any suspected breach to NBI and the company **within 24 hours**, in line with Nepal's Individual Privacy Act, 2075.

Send this report as soon as a breach is suspected, even before the facts are complete. A suspicion is enough; do not wait for proof. Send an update when more is known.

**The report itself never contains personal data.** Describe what kind of data and how many cases, never names, contact details, descriptions or reference numbers with PINs.

## Who receives it

| Organisation | Contact | How |
|---|---|---|
| NBI | Fill in at kick-off | |
| The company | Fill in at kick-off | |

## The report

Copy everything below into the email or letter and fill it in.

---

**Subject:** Suspected data breach, grievance handling system, reported [date and time, Nepal time]

1. **Reported by:** [name and role at the vendor]
2. **When the breach was suspected:** [date and time, Nepal time]
3. **When it may have started:** [date and time, or "not yet known"]
4. **How it was noticed:** [for example: an alert, an unusual entry in the activity log, a report from staff or a complainant]
5. **What happened, as far as is known:** [plain description: lost device, unauthorised sign-in, exposed backup, wrong email recipient, and so on]
6. **Parts of the system involved:** [public form, status page, staff panel, database, attachments, backups, email, server]
7. **Kinds of data that may be affected:** [tick what applies]
   - [ ] Complainant names
   - [ ] Phone numbers or email addresses
   - [ ] Grievance descriptions, messages or internal notes
   - [ ] Demographic answers (complainant type, sex, age group, ethnicity, disability)
   - [ ] Attachments (photos, PDFs)
   - [ ] Staff accounts (names, email addresses, sign-in details)
8. **How many cases may be affected:** [number, or an estimate and how it was made]
9. **Restricted cases among them:** [yes, no, or not yet known]
10. **Was the data encrypted where it was exposed?** Names, contact details, descriptions, messages, notes and demographic answers are stored encrypted with the application key; backups are encrypted with their own password. [say whether the application key or the backup password may also be exposed]
11. **What has been done so far:** [for example: passwords changed, sessions ended, the site taken offline, keys rotated, access removed]
12. **What happens next, and when:** [next steps and the time of the next update]
13. **Contact for questions:** [name, phone, email of the vendor's contact]

---

## After sending

- Record the report in the support incident log (`support/incident-log.md` in the delivery repository, plan section 5): date, time sent, to whom.
- Keep any evidence (activity log entries, server logs) without copying personal data out of production.
