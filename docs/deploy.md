# Deploying

For whoever puts the grievance system on a server: staging first, with fake data only, then production.

It is written for the company's shared hosting with cPanel, SSH and cron. On a VPS only section 2, the folders, is different.

Every command below was rehearsed on 2026-11-02 on a developer machine, from a clean export without dev packages, in staging mode. Only a real host can check the parts that depend on it: the certificate, email delivery, cron, and the PHP settings for the website.

## 1. Check the host

Over SSH, on the server:

```sh
php -r 'echo "PHP ", PHP_VERSION, version_compare(PHP_VERSION, "8.4.1", ">=") ? " ok\n" : " TOO OLD: 8.4.1 or newer needed\n"; foreach (["ctype","dom","exif","fileinfo","filter","gd","hash","iconv","intl","json","libxml","mbstring","openssl","pcre","pdo_mysql","session","tokenizer","xmlreader","zip"] as $e) { if (! extension_loaded($e)) echo "missing extension: $e\n"; } if (function_exists("gd_info")) { foreach (["JPEG Support", "PNG Support", "WebP Support"] as $k) { if (empty(gd_info()[$k])) echo "gd lacks $k\n"; } } if (class_exists("ZipArchive") && ! defined("ZipArchive::EM_AES_256")) echo "zip lacks AES-256: backups would not be encrypted\n"; foreach (["upload_max_filesize", "post_max_size", "memory_limit"] as $k) echo "$k = ", ini_get($k), "\n";'
which mysqldump
```

| Needed | Why |
|---|---|
| PHP 8.4.1 or newer, for the website and on the command line | The locked packages (Symfony 8, `spatie/laravel-activitylog` 5) need it. With older PHP every page stops with "Your Composer dependencies require a PHP version >= 8.4.1" |
| No "missing extension", "gd lacks" or "zip lacks" lines | `gd` and `exif` redraw photos without their location and phone data (S5); `pdo_mysql` reaches the database; `zip` with AES-256 encrypts the nightly backup, which would otherwise be written unencrypted without any warning; the rest are for Laravel, Filament and the export |
| `upload_max_filesize` `6M`, `post_max_size` `32M` | Attachments up to 5 MB (P5, A3); PHP's default is `2M`. Above `post_max_size` PHP drops the whole form, so a complainant who picks a large phone photo would lose everything typed; with `32M` it is an ordinary "file too large" message and the text stays |
| `memory_limit` `512M`, or at least `256M` | Redrawing the largest photo the app accepts (25 megapixels) needs about 250 MB where GD is built into PHP |
| `mysqldump` found | The nightly backup (S7). If it is not on the path, ask the host where it is and add `'dump_binary_path' => '<its folder>'` to the `dump` option of the `mysql` connection in `config/database.php`. Make that change in the release you build (section 3), not on the server: every upload replaces the server's copy of the code |
| MySQL 8 or MariaDB 10.6+, with one database and one user for it | |
| Cron every minute | Emails, the nightly backup and the working-day count |
| A subdomain with a free certificate (AutoSSL or Let's Encrypt) | S1 |
| Outgoing SMTP, with SPF and DKIM for the sending domain | Alerts and acknowledgements (N1, N2), kept out of spam |
| If possible, web access logs off or short for the subdomain | They record visitors' IP addresses outside the app (S5) |

On cPanel, the website's PHP version is set in MultiPHP Manager and its `upload_max_filesize`, `post_max_size` and `memory_limit` in MultiPHP INI Editor. The command line can run a different PHP from the website: `php -v` over SSH shows the command line's. If it is older, use the full path of PHP 8.4, for example `/opt/cpanel/ea-php84/root/usr/bin/php`, wherever this guide says `php`, the cron line included.

## 2. Folders on the server

```text
/home/<account>/
  ghm/          the application: code, vendor/, .env, storage/ (attachments, backups, logs)
    public/     the only folder the web server serves
```

Point the subdomain's document root at `ghm/public` (cPanel: Domains, the subdomain, Document Root). Everything else in `ghm/` stays out of the web's reach, `.env`, attachments and backups included (S4, S7).

If the host cannot set that document root, replace the subdomain's folder (often `~/public_html/<subdomain>`, empty when new) with a link, and check on staging that the site loads: `rmdir ~/public_html/<subdomain> && ln -s ~/ghm/public ~/public_html/<subdomain>`.

On a VPS: the same folder, for example `/var/www/ghm`, with the web server's document root at `/var/www/ghm/public`, and the PHP settings from section 1 in its `php.ini`. There PHP often runs as a different user from the one who uploads the files. Give that user write access to `storage/` and `bootstrap/cache/`, and read access to `.env` (`chmod 640 .env` with the web server's group, instead of the `600` in section 5). Without it, every page shows a server error. On cPanel PHP runs as the account itself, and nothing needs changing.

## 3. Build a release

On a computer with PHP 8.4 and Composer. The release comes from a clean copy of the code, never from a folder someone has worked in, so no `.env`, test data or dev package can travel with it.

From the release zip (`ghm-system.zip`, as handed over):

```sh
rm -rf ../ghm-release && mkdir ../ghm-release
unzip -q ghm-system.zip -d ../ghm-release
cd ../ghm-release
composer install --no-dev --optimize-autoloader --no-interaction
```

From a Git repository of the code, replace the `unzip` line with `git archive <tag> | tar -x -C ../ghm-release`, where `<tag>` is the version to deploy, for example `v1.0.0`.

Keep the zip of every version you deploy: it is the code to go back to (section 6) and to rebuild a lost server from (`docs/restore.md`). `composer install` also publishes Filament's scripts and styles into `public/`. The release is about 110 MB. Node is not needed.

## 4. Upload

```sh
rsync -az --delete --exclude=/.env --exclude=/public/.user.ini --exclude=/public/php.ini --filter='protect /storage/**' ./ <user>@<host>:ghm/
```

Run it from `../ghm-release`. The same command does the first upload and every update. It never touches the server's `.env` or anything stored under `storage/` (attachments, backups, sessions, logs), and it deletes files a new version no longer has, stale caches included.

On cPanel, MultiPHP INI Editor keeps the PHP settings from section 1 in `public/.user.ini` and `public/php.ini`; the command leaves both alone. MultiPHP Manager keeps the PHP version in a block it adds to `public/.htaccess`, and every upload replaces that file with the release's copy. So after every upload, check the PHP version (section 7). On staging, note which of these files cPanel creates.

## 5. First deploy

On the server, in `~/ghm`:

1. Create `.env` from the template below, then `chmod 600 .env`.
2. Make the encryption key, **once in the life of the installation**:

   ```sh
   php artisan key:generate
   ```

   Never add `--force`: in production the command then asks before replacing an existing key, and the answer is always no. Put `APP_KEY` from `.env` into the company's password manager straight away, next to `BACKUP_ARCHIVE_PASSWORD`. Every name, contact, description, message and note is encrypted with it (S3). A new key, or a lost one, makes them unreadable for good (`docs/restore.md`).
3. Create the tables, then the first Administrator from the `GHM_ADMIN_*` lines. This also loads the company's categories (two of them restricted) and locations, on staging and in production. It fills empty lists only, so it never undoes a change made later in Settings:

   ```sh
   php artisan migrate --force
   php artisan db:seed --force
   ```

4. Delete the three `GHM_ADMIN_*` lines from `.env`.
5. Build the caches. `optimize` caches the configuration, events, routes, views and Filament's components:

   ```sh
   php artisan optimize
   ```

6. Add the cron line (cPanel: Cron Jobs, "Once Per Minute"), with the PHP 8.4 path from section 1 if needed:

   ```text
   * * * * * cd /home/<account>/ghm && php artisan schedule:run >> /dev/null 2>&1
   ```

7. Make the first backup now, so a backup problem shows today rather than in tomorrow's failure email:

   ```sh
   php artisan backup:run
   ```

   It ends with "Backup completed!". Anything else: `docs/troubleshooting.md`, Backups.
8. Run the checks in section 7. Sign in as the Administrator, which sets up two-factor login, and change the password on the profile page. The Administrator then adds the staff in Settings → Users (`docs/admin-guide.md`).

### `.env` template

```dotenv
APP_NAME="Grievance handling"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://<subdomain>
APP_LOCALE=ne
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=14
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=file
SESSION_LIFETIME=30
CACHE_STORE=file
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"

BACKUP_ARCHIVE_PASSWORD=
BACKUP_DISKS=backups
BACKUP_MAIL_TO=

GHM_ADMIN_NAME=
GHM_ADMIN_EMAIL=
GHM_ADMIN_PASSWORD=
```

| Line | Note |
|---|---|
| `APP_ENV` | `staging` on the staging server. Anything but `local` and `testing` sends plain HTTP to HTTPS (S1) |
| `APP_DEBUG` | Always `false` on a server: error pages would show code and settings |
| `APP_NAME` | Shows in emails, the staff panel and authenticator apps; the company's name replaces it at kick-off |
| `LOG_*` | One log file a day, kept 14 days, warnings and errors only. The log holds no personal data (`docs/technical-reference.md`) |
| `SESSION_LIFETIME` | 30 minutes idle and staff are signed out (S2). Leave `SESSION_SECURE_COOKIE` out: cookies are then Secure over HTTPS |
| `MAIL_*` | The company's mail server or sending service. Port 587 with `MAIL_SCHEME=null` (STARTTLS); for port 465 use `MAIL_SCHEME=smtps`. Never `MAIL_MAILER=log`: it writes every email, PINs included, into the log |
| `BACKUP_ARCHIVE_PASSWORD` | Required. Without it the nightly archive is not encrypted. Keep it in the password manager with `APP_KEY` |
| `BACKUP_DISKS` | `backups` only, until an off-server copy is built (`docs/technical-reference.md`, Backups) |
| `BACKUP_MAIL_TO` | Required. Who is told when a backup fails or is missing. Without it nobody is told |
| `GHM_ADMIN_*` | First deploy only; deleted after `db:seed` |
| `GHM_SUBMISSIONS_PER_HOUR`, `GHM_FAILED_LOOKUPS_PER_HOUR` | Optional; leave them out for 10 each. Grievances accepted, and failed status lookups, per network per hour (P8, P9). Raise the first before posters go up if many workers share one Wi-Fi |

If the host puts the site behind a proxy or load balancer (for example Cloudflare), set trusted proxies in `bootstrap/app.php` before the first deploy. Without them every visitor has the proxy's address: where the proxy ends HTTPS the redirect loops (`docs/technical-reference.md`, HTTPS), and in every case the public limits (10 grievances an hour, 10 failed status lookups, see the table above) count all visitors as one, so real complainants get refused.

## 6. Updating to a new version

1. Build the new version (section 3).
2. On the server, in `~/ghm`, take a backup and close the site:

   ```sh
   php artisan backup:run
   php artisan down
   ```

3. Upload (section 4).
4. On the server:

   ```sh
   php artisan migrate --force
   php artisan optimize
   php artisan up
   ```

5. Run the checks in section 7.

While the site is down, visitors get a "Service unavailable" page and scheduled tasks wait, emails included; they run again after `up`.

To go back, deploy the previous version the same way. If the new version changed the database, restore the backup from step 2 (`docs/restore.md`).

## 7. Checks after every deploy

| Check | Expect |
|---|---|
| `php artisan about` | Environment `production` (or `staging`), Debug Mode `OFF`; Config, Events, Routes and Views `CACHED`; under Backups, Archive password `set` (a red `NOT SET` means the nightly archive is not encrypted) |
| `php artisan schedule:list` | Eight tasks: the queue worker every minute, the backup at 01:00, its clean-up and check, the working-day recount (which also alerts the Administrator to a case past its target), failed-job pruning, the upload clean-up and the cache clean-up, both hourly |
| QR poster, in the staff panel | The address under the code is the site's own `https://` address. Print posters only after this check: the code is made from the address the panel was opened with (P10) |
| Settings, Users, in the staff panel | At least one active restricted-case officer. New restricted cases alert only them (N1); with none, the Administrator is alerted instead, and is the only one who can work them |
| From a developer machine: `BASE_URL=https://<subdomain> npx playwright test` | The `@smoke` specs pass: pages load over HTTPS with the security headers, and the staff sign-in is reachable. Against a non-local address Playwright runs nothing else |
| `http://<subdomain>` in a browser | It moves to `https://` |
| cPanel: MultiPHP Manager, then MultiPHP INI Editor | The subdomain still runs PHP 8.4, and `upload_max_filesize`, `post_max_size` and `memory_limit` are still those of section 1. An upload replaces `public/.htaccess`, where MultiPHP Manager keeps the version: if another version shows, choose 8.4 again. With an older PHP every page fails |
| [SSL Labs](https://www.ssllabs.com/ssltest/) for the subdomain | Grade A (S1) |
| The morning after: `ls storage/app/backups/ghm/` | Last night's zip (S7) |

On staging also:

| Check | Expect |
|---|---|
| The full suite: `BASE_URL=https://<staging> E2E_FULL=1 E2E_ARTISAN="ssh <user>@<host> php ghm/artisan" npx playwright test` | Every spec passes. Fake users are made through `ghm:e2e-user`, which production refuses |
| Enter an offline case with your own email address | The acknowledgement arrives within two minutes, not in spam (N2) |
| Upload a large photo straight from a phone camera to a fake case | It is stored and downloads without its location data: proves the upload size and memory settings (P5, S5) |
| The timed restore drill (`docs/restore.md`) | Done once before the 18 Nov test version (S7, X2) |

## 8. Staging and production

| | Staging | Production |
|---|---|---|
| `APP_ENV` | `staging` | `production` |
| Data | Fake only | Real. The vendor signs in only with the company's written permission, each time (S8) |
| Categories and locations | The company's lists, from `db:seed` | The company's lists, from `db:seed`; changed later in Settings |
| Playwright | The full suite | `@smoke` only |
| First Administrator | A test account | The company's own, named at kick-off |
| Before go-live | | Every UAT case cleared |
