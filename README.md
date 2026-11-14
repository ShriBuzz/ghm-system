# Grievance handling system

The online grievance handling system built for RFQ NBI/GHM/RFQ/01. It has two parts:

- **Public side.** Anyone can send a grievance, named or anonymous, in Nepali or English, and later follow it on the status page with a reference number and PIN.
- **Staff panel** at `/admin`. Three roles: Administrator, Focal person and Restricted-case officer. Staff work cases, write internal notes, reply to complainants and export data.

Built with Laravel 13 and Filament 5 on PHP 8.4. It runs on ordinary shared hosting with MySQL or MariaDB.

## Start here

| You are | Read |
|---|---|
| A focal person or restricted-case officer | `docs/user-guide.md`, the user guide (5 pages) |
| The Administrator | The user guide, then `docs/admin-guide.md` (staff accounts, categories, the public notice, restricted cases, the activity log) |
| The IT contact, who looks after the server | `docs/technical-note.md` (one page), then `docs/deploy.md`, `docs/restore.md` and `docs/troubleshooting.md` |
| A developer changing the code | `docs/technical-reference.md`, `docs/local-setup.md` and `docs/requirements-traceability.md` |

The guides are plain text with pictures, in the `docs/` folder. They also come as PDF files for printing.

## Handover package

| Item | Where |
|---|---|
| Source code | This folder, handed over as a zip |
| Database structure | `database/migrations/`, and `docs/database-schema.sql` in one file |
| Administrator, hosting and database credentials | Handed over in person on a signed sheet, straight into the company's password manager; never kept in this folder. The code goes over as a zip, so there is no repository login |
| User guide for focal persons | `docs/user-guide.md` |
| Technical note: framework, libraries, installation, backup and restore | `docs/technical-note.md` |

## All documents

| Document | What it covers |
|---|---|
| `docs/user-guide.md` | Working cases in the staff panel, for focal persons and restricted-case officers |
| `docs/admin-guide.md` | What only the Administrator does |
| `docs/technical-note.md` | The system on one page: framework, libraries, installation, backup and restore |
| `docs/deploy.md` | Putting the system on a server, and updating it |
| `docs/restore.md` | Restoring a backup |
| `docs/troubleshooting.md` | What to check, and how to fix it, when something goes wrong on the server |
| `docs/technical-reference.md` | Security, sessions, email, files, backups and the activity log in detail; why each library is used |
| `docs/requirements-traceability.md` | Each RFQ requirement, the code that meets it and its tests |
| `docs/rfq-requirements.md` | The RFQ requirements, word for word |
| `docs/database-schema.sql` | The database tables in one file, for reading |
| `docs/local-setup.md` | Setting up a developer machine |
| `docs/manual-testing.md` | Trying every feature by hand with fake data |
