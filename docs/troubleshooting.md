# Troubleshooting

For the company's IT contact: what to check, and how to fix it, when something goes wrong on the server. Run every command over SSH from the application folder (`~/ghm` in `docs/deploy.md`), with the PHP 8.4 path if the server needs it.

Staff questions that need no server access (a forgotten password, a new user, a category) are for the Administrator: `docs/admin-guide.md`.

## First checks

| Command | What it tells you |
|---|---|
| `php artisan about` | Environment `production`, Debug Mode `OFF`, caches `CACHED`, and under Backups whether the archive password is set |
| `ls -t storage/logs/ \| head -3`, then `tail -50 storage/logs/laravel-<date>.log` | The latest errors. One file a day, kept 14 days, warnings and errors only |
| `php artisan schedule:list` | The eight scheduled tasks and when each runs next. If "next due" times are in the past, cron is not running |
| `php artisan queue:failed` | Emails that failed four times |
| `php artisan backup:list` | The newest backup, how many there are, the space they use, and whether they are healthy |

The log holds no personal data: no names, contacts, case text, IP addresses or browser details. You can send its lines to the vendor for support as they are.

## Staff sign-in

**"Too many login attempts".** Five wrong passwords lock that account for 15 minutes, counted from the fifth. Waiting is the fix. To lift it at once, `php artisan cache:clear`. That also lifts every other lockout and resets the public form's hourly limits; it signs nobody out, because sessions are stored apart from the cache.

**Forgotten password.** The Administrator sets a new one: Settings → Users → Edit → Password. The user changes it on Profile after signing in.

**Lost phone and no recovery codes left.** Nothing in the panel resets two-factor login. Clear it on the server, then the user sets it up again at the next sign-in, as on the first day:

```sh
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'their.address@company')->firstOrFail();
$user->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save();
exit
```

Check who is asking before you do it: whoever holds the account's password then gets in. Note it in the support log.

**The only Administrator is locked out** (lost password and phone). The same, setting a temporary password as well:

```php
$user = App\Models\User::where('email', 'admin.address@company')->firstOrFail();
$user->forceFill(['password' => 'a-temporary-password', 'app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save();
exit
```

The password is hashed on save. Tinker keeps a history file, so the Administrator changes the temporary password on Profile straight away.

**Signed out after 30 minutes.** Expected: 30 idle minutes end the session (S2). A form the user had not saved is lost.

**A user's right password is refused.** Settings → Users: check **Active**. A deactivated user sees the same "These credentials do not match our records" as for a wrong password, on purpose.

## Emails

Every email is queued in the `jobs` table and sent by the worker that cron starts every minute.

1. **Is cron running?** `php artisan schedule:list`. If the "next due" times are in the past, check the cron line (`docs/deploy.md`, section 5). `php artisan schedule:run` runs it once by hand.
2. **Are emails waiting?** `php artisan tinker --execute="echo DB::table('jobs')->count();"`. A number that keeps growing means the worker is not running (cron) or cannot reach the mail server.
3. **Did some fail?** `php artisan queue:failed`. Each failed email was tried four times over about an hour. The error shows the mail server's code, never the recipient. After fixing the cause, `php artisan queue:retry all`. Failed emails are kept 7 days.
4. **Is the mail server right?** Send a test from tinker to your own address. It shows the full error on screen and logs nothing:

   ```sh
   php artisan tinker --execute="Mail::raw('test', fn (\$m) => \$m->to('you@company')->subject('test'));"
   ```

   Port 465 needs `MAIL_SCHEME=smtps`, port 587 `MAIL_SCHEME=null`. After changing `.env`, `php artisan config:cache`.
5. **Sent but not received?** Check the spam folder, then the SPF and DKIM records for the sending domain (`docs/deploy.md`, section 1).

## Backups

The nightly backup runs at 01:00 and emails `BACKUP_MAIL_TO` only when something is wrong. To see the error on screen, run it by hand:

```sh
php artisan backup:run
```

| Error contains | Fix |
|---|---|
| `mysqldump: not found` or `command not found` | Ask the host where `mysqldump` is, and set `dump_binary_path` (`docs/deploy.md`, section 1) |
| `Access denied` and `LOCK TABLES` | The database user needs the LOCK TABLES privilege. On cPanel: MySQL Databases, the user, ALL PRIVILEGES |
| `No space left` or `Disk quota exceeded` | The account is full. `du -sh storage/app/backups storage/logs`. Archives may use 5,000 MB in all; the host's quota may be smaller |
| Backup completes, but `php artisan about` shows `NOT SET` | `BACKUP_ARCHIVE_PASSWORD` is empty: the archives are not encrypted. Set it, then `php artisan config:cache` |

**"Unhealthy backup" email** from the 02:00 check: the newest backup is older than a day (the backup failed, or cron stopped), or the archives passed 5,000 MB. `php artisan backup:list` shows which.

Restoring a backup: `docs/restore.md`.

## Error pages

**"Server Error" (500).** Read the newest log file. The usual causes:

| Log says | Fix |
|---|---|
| `Permission denied` under `storage/` or `bootstrap/cache/` | PHP cannot write there (`docs/deploy.md`, section 2) |
| `Your Composer dependencies require a PHP version >= 8.4.1` | The website runs an older PHP. On cPanel: MultiPHP Manager |
| `No application encryption key has been specified` | `APP_KEY` is missing from `.env`. Put back the one from the password manager. Never generate a new one on a live site |
| `The MAC is invalid` or `DecryptException` | `.env` has a different `APP_KEY` from the one the data was encrypted with. Put back the right one, then `php artisan config:cache` |
| `SQLSTATE[HY000] [1045]` or `[2002]` | The `DB_*` lines in `.env` are wrong, or the database is down |

After any change to `.env`, run `php artisan config:cache`: the site reads the cached copy, not the file.

**"Service Unavailable" (503).** The site was left closed by `php artisan down`. Open it with `php artisan up`.

**The browser says the page redirects too many times.** The site sits behind a proxy that ends HTTPS. Set trusted proxies (`docs/deploy.md`, section 5).

**"Page Expired" (419) in the staff panel.** The session ended while the page was open. Sign in again.

## Public form and status page

**Complainants are told "Many grievances were just sent from the same Wi-Fi or mobile network".** The form accepts 10 grievances an hour from one network. If many people share one Wi-Fi, raise it: add `GHM_SUBMISSIONS_PER_HOUR=30` (or another number) to `.env`, then `php artisan config:cache`.

**"This reference number is locked for 15 minutes after 5 wrong PINs".** It unlocks by itself after 15 minutes. `php artisan cache:clear` lifts it at once.

**A complainant lost their PIN.** It cannot be shown again or looked up: only its hash is stored.

**A complainant's large photo fails or the whole form comes back empty.** The PHP limits are too low: `upload_max_filesize` 6M, `post_max_size` 32M, `memory_limit` 512M (`docs/deploy.md`, section 1).

**The QR poster shows the wrong address.** The code is made from the address the panel was opened with. Open the panel at the site's own `https://` address and print again.

## Restricted cases

**The Administrator gets an email that a restricted case arrived with no restricted-case officer.** No active user has that role. The Administrator adds or reactivates one in Settings → Users.

## The encryption key

`APP_KEY` in `.env` encrypts names, contacts, descriptions, the optional answers, messages, notes, file names, staff two-factor secrets, sessions and queued acknowledgement emails.

- Never run `php artisan key:generate` on a live site. A new key makes all of that unreadable, for good.
- Keep it in the company's password manager, with `BACKUP_ARCHIVE_PASSWORD`. A backup restores only with both.
- To replace a key that may have leaked: put the old key in `APP_PREVIOUS_KEYS`, the new one in `APP_KEY`, then `php artisan config:cache`. Data encrypted with the old key stays readable, and new data uses the new key. Keep the old key in the password manager as long as any backup made with it is kept.
