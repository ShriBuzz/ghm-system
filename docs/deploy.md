# Deploying

For whoever puts the grievance system on a server: staging first, with fake data only, then production.

It is written for the plan's first choice of host: the company's shared hosting with cPanel, SSH and cron (plan section 3, H1). On a VPS (H2) only section 2, the folders, is different. The host is not known yet (question 1 in `docs/clarification-questions.md`).

Every command below was rehearsed on 2026-11-02 on a developer machine, from a clean export without dev packages, in staging mode. Only a real host can check the parts that depend on it: the certificate, email delivery, cron, and the PHP settings for the website.

## 1. Check the host

Over SSH, on the server:

```sh
php -r 'echo "PHP ", PHP_VERSION, version_compare(PHP_VERSION, "8.4.1", ">=") ? " ok\n" : " TOO OLD: 8.4.1 or newer needed\n"; foreach (["ctype","dom","exif","fileinfo","filter","gd","hash","iconv","intl","json","libxml","mbstring","openssl","pcre","pdo_mysql","session","tokenizer","xmlreader","zip"] as $e) { if (! extension_loaded($e)) echo "missing extension: $e\n"; } if (function_exists("gd_info")) { foreach (["JPEG Support", "PNG Support", "WebP Support"] as $k) { if (empty(gd_info()[$k])) echo "gd lacks $k\n"; } } foreach (["upload_max_filesize", "post_max_size", "memory_limit"] as $k) echo "$k = ", ini_get($k), "\n";'
which mysqldump
```

| Needed | Why |
|---|---|
| PHP 8.4.1 or newer, for the website and on the command line | The locked packages (Symfony 8, `spatie/laravel-activitylog` 5) need it. With older PHP every page stops with "Your Composer dependencies require a PHP version >= 8.4.1" |
| No "missing extension" or "gd lacks" lines | `gd` and `exif` redraw photos without their location and phone data (S5); `pdo_mysql` reaches the database; the rest are for Laravel, Filament, the export and the backups |
| `upload_max_filesize` at least `6M`, `post_max_size` at least `8M` | Attachments up to 5 MB (P5, A3). PHP's default is `2M` |
| `memory_limit` `512M`, or at least `256M` | Redrawing the largest photo the app accepts (25 megapixels) needs about 250 MB where GD is built into PHP |
| `mysqldump` found | The nightly backup (S7). If it is not on the path, ask the host where it is and add `'dump' => ['dump_binary_path' => '<its folder>']` to the `mysql` connection in `config/database.php` |
| MySQL 8 or MariaDB 10.6+, with one database and one user for it | |
| Cron every minute | Emails, the nightly backup and the working-day count |
| A subdomain with a free certificate (AutoSSL or Let's Encrypt) | S1 |
| Outgoing SMTP, with SPF and DKIM for the sending domain | Alerts and acknowledgements (N1, N2), kept out of spam |
| If possible, web access logs off or short for the subdomain | They record visitors' IP addresses outside the app (S5, question 4) |

On cPanel, the website's PHP version is set in MultiPHP Manager and its `upload_max_filesize`, `post_max_size` and `memory_limit` in MultiPHP INI Editor. The command line can run a different PHP from the website: `php -v` over SSH shows the command line's. If it is older, use the full path of PHP 8.4, for example `/opt/cpanel/ea-php84/root/usr/bin/php`, wherever this guide says `php`, the cron line included.

## 2. Folders on the server

```text
/home/<account>/
  ghm/          the application: code, vendor/, .env, storage/ (attachments, backups, logs)
    public/     the only folder the web server serves
```

Point the subdomain's document root at `ghm/public` (cPanel: Domains, the subdomain, Document Root). Everything else in `ghm/` stays out of the web's reach, `.env`, attachments and backups included (S4, S7).

If the host cannot set that document root, replace the subdomain's folder (often `~/public_html/<subdomain>`, empty when new) with a link, and check on staging that the site loads: `rmdir ~/public_html/<subdomain> && ln -s ~/ghm/public ~/public_html/<subdomain>`.

On a VPS: the same folder, for example `/var/www/ghm`, with the web server's document root at `/var/www/ghm/public`, and the PHP settings from section 1 in its `php.ini`.

## 3. Build a release

On a developer machine with PHP 8.4 and Composer. The release comes from a clean export, never from a working copy, so no `.env`, test data or dev package can travel with it:

```sh
rm -rf ../ghm-release && mkdir ../ghm-release
git archive <tag> | tar -x -C ../ghm-release
cd ../ghm-release
composer install --no-dev --optimize-autoloader --no-interaction
```

`<tag>` is the version to deploy, for example `v1.0.0`; `main` for a staging build between versions. `composer install` also publishes Filament's scripts and styles into `public/`. The release is about 110 MB. Node is not needed.

## 4. Upload

```sh
rsync -az --delete --exclude=/.env --filter='protect /storage/**' ./ <user>@<host>:ghm/
```

Run it from `../ghm-release`. The same command does the first upload and every update. It never touches the server's `.env` or anything stored under `storage/` (attachments, backups, sessions, logs), and it deletes files a new version no longer has, stale caches included.

## 5. First deploy

On the server, in `~/ghm`:

1. Create `.env` from the template below, then `chmod 600 .env`.
2. Make the encryption key, **once in the life of the installation**:

   ```sh
   php artisan key:generate
   ```

   Never add `--force`: in production the command then asks before replacing an existing key, and the answer is always no. Put `APP_KEY` from `.env` into the company's password manager straight away, next to `BACKUP_ARCHIVE_PASSWORD`. Every name, contact, description, message and note is encrypted with it (S3). A new key, or a lost one, makes them unreadable for good (`docs/restore.md`).
3. Create the tables, then the first Administrator from the `GHM_ADMIN_*` lines. On staging this also loads the placeholder categories and locations; production gets the real lists through Settings:

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

7. Run the checks in section 7. Sign in as the Administrator, which sets up two-factor login, and change the password on the profile page.

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
| `LOG_*` | One log file a day, kept 14 days, warnings and errors only. The log holds no personal data (technical note) |
| `SESSION_LIFETIME` | 30 minutes idle and staff are signed out (S2). Leave `SESSION_SECURE_COOKIE` out: cookies are then Secure over HTTPS |
| `MAIL_*` | The company's mail server or sending service (question 3). Never `MAIL_MAILER=log`: it writes every email, PINs included, into the log |
| `BACKUP_ARCHIVE_PASSWORD` | Required. Without it the nightly archive is not encrypted. Keep it in the password manager with `APP_KEY` |
| `BACKUP_DISKS` | Add the off-server copy here once the host is known (`docs/technical-note.md`, Backups) |
| `BACKUP_MAIL_TO` | Who is told when a backup fails or is missing |
| `GHM_ADMIN_*` | First deploy only; deleted after `db:seed` |

If the host puts the site behind a proxy or load balancer that ends HTTPS (for example Cloudflare), set trusted proxies in `bootstrap/app.php` before the first deploy, or the HTTPS redirect loops (technical note, HTTPS).

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
| `php artisan about` | Environment `production` (or `staging`), Debug Mode `OFF`; Config, Events, Routes and Views `CACHED` |
| `php artisan schedule:list` | Seven tasks: the queue worker every minute, the backup at 01:00, its clean-up and check, the working-day recount, failed-job pruning and the upload clean-up |
| From a developer machine: `BASE_URL=https://<subdomain> npx playwright test` | The `@smoke` specs pass: pages load over HTTPS with the security headers, and the staff sign-in is reachable. Against a non-local address Playwright runs nothing else |
| `http://<subdomain>` in a browser | It moves to `https://` |
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
| Data | Fake only | Real. The vendor signs in only with the company's written permission, each time (`docs/production-access-log.md`, S8) |
| Categories and locations | Placeholders from `db:seed` | The real lists, entered in Settings |
| Playwright | The full suite | `@smoke` only |
| First Administrator | A test account | The company's own, named at kick-off |
| Before go-live | | Every UAT case cleared (plan section 10) |
