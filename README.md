# Grievance handling system

The online grievance handling system built for RFQ NBI/GHM/RFQ/01. It has two parts:

- **Public side.** Anyone can send a grievance, named or anonymous, in Nepali or English, and later follow it on the status page with a reference number and PIN.
- **Staff panel** at `/admin`. Three roles: Administrator, Focal person and Restricted-case officer. Staff work cases, write internal notes, reply to complainants and export data.

Built with Laravel 13 and Filament 5 on PHP 8.4. It runs on ordinary shared hosting with MySQL or MariaDB.

## Handover package

| Item | Where |
|---|---|
| Source code | This folder |
| Database structure | `database/migrations/`, and `docs/database-schema.sql` in one file |
| Administrator, hosting and database credentials | Handed over separately into the company's password manager; never kept in this folder |
| User guide for focal persons | `docs/user-guide.md` |
| Technical note: framework, libraries, installation, backup and restore | `docs/technical-note.md` |

## Other documents

| Document | What it covers |
|---|---|
| `docs/deploy.md` | Putting the system on a server, and updating it |
| `docs/restore.md` | Restoring a backup |
| `docs/technical-reference.md` | Security, sessions, email, files, backups and the activity log in detail; why each library is used |
| `docs/requirements-traceability.md` | Each RFQ requirement, the code that meets it and its tests |
| `docs/rfq-requirements.md` | The RFQ requirements, word for word |
| `docs/local-setup.md` | Setting up a developer machine |
| `docs/manual-testing.md` | Trying every feature by hand with fake data |
