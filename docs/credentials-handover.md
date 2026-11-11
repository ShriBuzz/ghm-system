# Credentials handover sheet

For the handover on go-live day. It lists every credential the company receives, where each is kept afterwards, and what to change.

**Never fill this sheet in Git.** Print it, or export it to PDF, and fill it in by hand. The values themselves go straight into the company's password manager, so the sheet only names the entry that holds each one. If the company has no password manager yet, hand the values over on a separate sheet in a sealed envelope, never by email or chat.

The source code is handed over as a zip of the release, without Git history. So there is no repository login to hand over.

| # | Credential | Used for | Password manager entry | Change after handover? | Handed over (date, initials) |
|---|---|---|---|---|---|
| 1 | Staff panel: the company's Administrator account | Users, categories, locations, holidays, every case | | The company's Administrator signs in, sets up two-factor login and changes the password on Profile. Keep the recovery codes with this entry | |
| 2 | Staff panel: the vendor's account | Support only | | Deactivated by the Administrator at handover. Reactivated only for support the company agrees to in writing (S8) | |
| 3 | Hosting control panel (for example cPanel) | Domains, PHP settings, databases, cron, email | | Yes, if the vendor ever knew it | |
| 4 | SSH or SFTP login | Updates, backups, restore | | Kept for the support year with the company's agreement, then removed or changed | |
| 5 | Database name, user and password | The `DB_*` lines in `.env` | | Optional. Change it in the hosting panel, then update `DB_PASSWORD` in `.env` and run `php artisan config:cache` | |
| 6 | Mail account (SMTP) | Alerts and acknowledgements | | Optional. Same steps, with the `MAIL_*` lines | |
| 7 | `APP_KEY` | Encrypts names, contacts, descriptions, messages and notes | | **Never.** A new key makes the stored data unreadable | |
| 8 | `BACKUP_ARCHIVE_PASSWORD` | Opens the nightly backup archives | | Optional. Archives made before the change still need the old password, so keep it for 15 days | |
| 9 | Off-server backup storage | The second copy of each backup | | Yes, if the vendor ever knew it | |
| 10 | Domain and DNS | The grievance subdomain; the SPF and DKIM mail records | | Only if the vendor set them up | |

Not on this sheet: the other staff users' passwords. Each user sets their own at first sign-in and changes it on Profile.

## Sign-off

| | Name | Role | Signature | Date |
|---|---|---|---|---|
| Handed over by (vendor) | | | | |
| Received by (company) | | | | |
| Witness (NBI), if present | | | | |
