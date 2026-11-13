# Technical note

The grievance handling system (RFQ NBI/GHM/RFQ/01). Detail in `docs/`: `deploy.md` (install, update), `restore.md`, `troubleshooting.md`, `technical-reference.md`.

## Framework and version

PHP 8.4.1+ with `gd`, `exif` and `zip`; Laravel 13.39; Filament 5.11 on Livewire 4.4 (staff panel at `/admin`); MySQL 8 or MariaDB 10.6+. No build step; nothing on the server needs Node.

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

1. Host (`deploy.md`, section 1): PHP 8.4.1+ and extensions; `upload_max_filesize` 6M, `post_max_size` 32M, `memory_limit` 512M; `mysqldump`, cron, SMTP.
2. Point the subdomain's document root at `public/`.
3. Unzip the release, `composer install --no-dev --optimize-autoloader`, `rsync` it up.
4. `.env` from the template in `deploy.md`; `php artisan key:generate`, once ever.
5. `php artisan migrate --force`; `php artisan db:seed --force` (first Administrator, categories, locations); delete `GHM_ADMIN_*`.
6. `php artisan optimize`; cron `* * * * * cd <app folder> && php artisan schedule:run`; `php artisan backup:run`.

## Backup and restore

**Backup.** Nightly at 01:00 Nepal time: database and uploads in one AES-256 zip (`BACKUP_ARCHIVE_PASSWORD`) in `storage/app/backups/ghm/`, kept 15 days; an email above 5,000 MB. Failures email `BACKUP_MAIL_TO`. No off-server copy yet: it needs storage from the host and a release.

**Restore** (`restore.md`). `php artisan down`; unzip with the archive password; load the dump; copy `attachments/` back; with the backup's `APP_KEY`, `migrate --force`, `config:cache`, `up`.

**Keys.** `APP_KEY` encrypts every personal field, two-factor secrets and sessions. Keep it with `BACKUP_ARCHIVE_PASSWORD` in the password manager: a backup restores only with both. Never make a new `APP_KEY` on a live system.

## Database structure

`database/migrations/` creates the 18 tables, shown in one file in `docs/database-schema.sql`. Encrypted: name, contacts, description and the optional answers in `grievances`; `body` in `grievance_messages` and `grievance_notes`; `original_name` in `attachments`; the two-factor secret and recovery codes in `users`.
