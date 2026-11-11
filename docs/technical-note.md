# Technical note

The grievance handling system (RFQ NBI/GHM/RFQ/01). Detail: `docs/technical-reference.md`.

## Framework and version

PHP 8.4.1+ with `gd` and `exif`, Laravel 13.39, Filament 5.11 on Livewire 4.4 (staff panel at `/admin`), MySQL 8 or MariaDB 10.6+. Public pages: plain Blade, one stylesheet, no build step, no Node.

## Libraries used

All free and open source. Dev tools (Pest, Larastan, Pint, Playwright) stay off the server.

| Library | Version | Licence | Used for |
|---|---|---|---|
| `laravel/framework` | 13.39 | MIT | Framework, encryption (S3), mail queue |
| `filament/filament` | 5.11 | MIT | Staff panel, sign-in, two-factor login |
| `spatie/laravel-activitylog` | 5.1 | MIT | Activity log (S6) |
| `spatie/laravel-backup` | 10.3 | MIT | Nightly encrypted backup (S7) |
| `openspout/openspout` | 4.32 | MIT | CSV and Excel export (N4) |
| `chillerlan/php-qrcode` | 5.0 | MIT or Apache-2.0 | QR poster (P10) |
| `laravel/tinker` | 3.0 | MIT | Maintenance console |

## Installation

Full steps: `docs/deploy.md`.

1. Check the host: PHP 8.4.1+ and extensions, `upload_max_filesize` 6M, `post_max_size` 32M, `mysqldump`, cron, SMTP.
2. Point the subdomain's document root at `public/`.
3. Build: `git archive`, then `composer install --no-dev --optimize-autoloader`; `rsync` it up.
4. Create `.env` from the template in `docs/deploy.md`; `php artisan key:generate`, once ever.
5. `php artisan migrate --force` and `php artisan db:seed --force` (first Administrator), then delete `GHM_ADMIN_*`.
6. `php artisan optimize`; cron line `* * * * * cd <app folder> && php artisan schedule:run`.

## Backup and restore

**Backup.** Nightly at 01:00 Nepal time: the database and uploaded files in one AES-256 zip (`BACKUP_ARCHIVE_PASSWORD`), in `storage/app/backups/` and any disk in `BACKUP_DISKS`, kept 15 days. Failures email `BACKUP_MAIL_TO`.

**Restore** (full steps: `docs/restore.md`). `php artisan down`; unzip with the archive password; load the SQL dump into the database; copy `attachments/` back to `storage/app/private/attachments/`; with the backup's `APP_KEY` in `.env`, `php artisan config:cache` and `php artisan up`.

**Keys.** `APP_KEY` encrypts names, contacts, descriptions, messages and notes. Keep it with `BACKUP_ARCHIVE_PASSWORD` in the company's password manager: a backup restores only with both. Never generate a new `APP_KEY` on a live system.

## Database structure

`database/migrations/` creates the tables; `docs/database-schema.sql` shows them in one file. Encrypted columns: name, contacts, description and the optional profile answers in `grievances`; `body` in `grievance_messages` and `grievance_notes`; `original_name` in `attachments`; the two-factor secret and recovery codes in `users`.
