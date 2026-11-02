# Testing by hand

How to run the GHM system on your machine and try what it does so far, by hand and with the automated checks. Set up the machine first with `docs/local-setup.md`.

Use fake data only. Never point the app, a test or a browser session at production (`CLAUDE.md`).

Last updated 2026-11-02, after WP3 (alerts, summary, export, backups). What each requirement covers so far is in `docs/requirements-traceability.md`.

## 1. Start fresh

A `.env` made before WP3 still has `APP_NAME=Laravel` and `MAIL_MAILER=log`. Change them to `.env.example`'s values, `APP_NAME="Grievance handling"` and `MAIL_MAILER=array`: with `log`, every email the app makes, PINs and addresses included, is written into `storage/logs/laravel.log`.

Give yourself an Administrator in `.env`, with fake details and a password you will remember:

```dotenv
GHM_ADMIN_NAME="Test Administrator"
GHM_ADMIN_EMAIL=admin@example.com
GHM_ADMIN_PASSWORD=pick-a-password-you-remember
```

Rebuild the local database. This deletes everything in it, which is only ever fake data:

```sh
php artisan migrate:fresh --seed
```

## 2. Run the app

```sh
php artisan serve
```

Open http://127.0.0.1:8000 and leave the server running; Ctrl+C stops it.

If another program already uses port 8000, `localhost:8000` can open that program instead of this app. Pick another port, for example `php artisan serve --port=8080`, and use that port in the addresses below.

## 3. Public pages

Open http://127.0.0.1:8000. It sends you to `/ne`, the Nepali page.

| Try | Expect |
|---|---|
| Click "English" in the header | The address becomes `/en`, the page is in English, and English is highlighted in the switch |
| Open http://127.0.0.1:8000 again | You land on `/en`: the last language is remembered |
| Chrome DevTools (Cmd+Opt+I), device toolbar (Cmd+Shift+M), width 360 | Everything fits, nothing scrolls sideways |
| DevTools, Cmd+Shift+P, "Disable JavaScript", reload | The page and the language switch still work |
| DevTools, Network tab, the page request | `Content-Security-Policy`, `Referrer-Policy: no-referrer` and `X-Content-Type-Options: nosniff` headers |

## 4. Staff panel

Open http://127.0.0.1:8000/admin and sign in with the email and password from `.env`.

1. The panel asks you to set up two-factor login. Scan the QR code with an authenticator app on your phone (Google Authenticator, Microsoft Authenticator, 1Password and similar), enter its 6-digit code and your password, and save the recovery codes it shows.
2. You land on the dashboard.
3. The avatar menu, top right, leads to your profile (`/admin/profile`): change your password there (the current one is required), regenerate recovery codes, or manage two-factor login.

| Try | Expect |
|---|---|
| Sign out, sign in again | After the password, a 6-digit code is asked for |
| At the code step, choose to use a recovery code | One recovery code signs you in; the same code fails a second time |
| A made-up email with a wrong password, 6 times | The 6th attempt shows "too many login attempts"; that email stays locked for 15 minutes. `php artisan cache:clear` clears locks early |
| `SESSION_LIFETIME=1` in `.env`, sign in, leave the tab for about 90 seconds | The tab returns to the sign-in page by itself. Set it back to `30` afterwards |

## 5. Admin side: demo of the proof of concept

The staff panel is in English. Start from a clean local database, so the demo shows only what you create. This deletes everything in `ghm`, which is only ever fake data, and loads your Administrator plus placeholder categories and locations:

```sh
php artisan migrate:fresh --seed
php artisan serve
```

Use three browser windows that keep their sessions apart, for example a normal window, a private window and another browser: one per role. Each new account sets up its own two-factor login at first sign-in; one authenticator app can hold all three accounts.

**Administrator: settings (A7, A5)**

| Try | Expect |
|---|---|
| Sign in, open Settings → Categories | Five placeholder categories; "Sexual harassment or abuse" is restricted |
| Settings → Users → New user: a focal person and a restricted-case officer, with fake names, `@example.com` emails and a password of at least 8 characters | Both appear in the list with their role |
| Edit your own user | Role and Active cannot be changed: you cannot lock yourself out |
| Settings → Holidays → add a date | It appears; holidays can be deleted, other settings cannot (deactivate them instead) |

**Focal person: offline entry (A6)**

| Try | Expect |
|---|---|
| Sign in as the focal person | Menu shows Dashboard and Cases only, no Settings. Opening `/admin/categories` shows "Forbidden" |
| Cases → Enter offline case: channel Phone, category "Wages and benefits", a named complainant with a phone number | A slip with the reference (`GHM-2026-0001`) and a 6-digit PIN, and a Print slip button |
| Print slip, or the browser's print preview | Only the heading and the slip: no menu, no buttons, no staff name |
| Reload the slip page | Back on the case list: the PIN is shown only once |
| Enter another case: received date four weeks ago | In the list its days open is red (over 15 working days); the filter "Overdue only" shows just that case |
| Enter a case in "Sexual harassment or abuse", switch Anonymous on | The contact fields disappear. The slip still shows, but the case is not in the focal person's list afterwards |
| Open one of your cases, Set status, Set priority | Only the RFQ's statuses and priorities are offered |

**Restricted-case officer and Administrator: who sees what (A1, A3, A5)**

| Try | Expect |
|---|---|
| Sign in as the officer, open Cases | Only the restricted case |
| Open it, Assign | Only the Administrator and the officer are offered, never a focal person |
| As the Administrator, open Cases | Every case. Search a reference number; filter by status, category, location or date |
| Assign a named case to the focal person, then Mark restricted | The assignment is cleared, and the case leaves the focal person's list |
| Remove restricted mark (Administrator only) | The focal person sees the case again |
| Settings → Categories → make "Wages and benefits" restricted | Its cases leave the focal person's list at once, past ones included |

## 6. Encryption in the database

Create a fake case and read the raw table:

```sh
php artisan tinker --execute 'App\Models\Grievance::factory()->create();'
mysql -u ghm -p ghm -e "SELECT reference, LEFT(name, 40), LEFT(email, 40) FROM grievances;"
```

The password `mysql` asks for is `DB_PASSWORD` in `.env`. Name and email show as ciphertext; the app decrypts them only when it reads them (S3).

## 7. Summary, export and emails (N1 to N4)

Carry on with the users and cases from section 5.

| Try | Expect |
|---|---|
| Summary in the menu, as each role | Cases received this month, counted by status and by category. The focal person's counts leave out restricted cases, the officer's count only those, the Administrator's count all |
| On Summary, set the end date before the start date | An error, and no counts |
| Cases → Export as the focal person: this month, CSV | A file of the cases they can see: reference, received and closed dates, channel, category, location, priority, status and the five demographic columns. No names or contacts, and no option to add them |
| The same as the Administrator, with "Include names and contact details" on | Name, phone and email columns after the reference. Opened in Excel or Numbers, Nepali text shows correctly |
| Activity log, as the Administrator | "Cases exported", with the dates, the format and whether personal data was included |

Emails are made but never sent locally (`MAIL_MAILER=array`). Entering an offline case queues an alert for the staff who can see it (not the one who entered it, never the Administrator) and, when the complainant gave an email, an acknowledgement. Work the queue the way the server's cron does:

```sh
php artisan queue:work --stop-when-empty
```

Each email shows a `RUNNING` line, then one ending in `DONE`; the worker stops once the queue is empty. To see what the two emails look like, render them for the newest case and open them in a browser (`open` is macOS; elsewhere open the files by hand):

```sh
php artisan tinker --execute '$case = App\Models\Grievance::latest("id")->firstOrFail(); app()->setLocale($case->locale); file_put_contents(storage_path("app/email-acknowledgement.html"), (new App\Notifications\Acknowledgement("123456"))->toMail($case)->render()); app()->setLocale("en"); file_put_contents(storage_path("app/email-alert.html"), (new App\Notifications\CaseAlert($case->id, message: false))->toMail(App\Models\User::firstOrFail())->render());'
open storage/app/email-acknowledgement.html storage/app/email-alert.html
```

| Look at | Expect |
|---|---|
| The acknowledgement | In the case's language (Nepali for offline cases): the reference, the PIN (here a made-up `123456`) and the public site's address. Nothing the complainant wrote |
| The alert | In English: "A new grievance has been received" and a "Log in to view" button. No reference, category, name or description |
| Both | The app's name as plain text at the top; no image or anything else from another site |

Delete the two files afterwards: `rm storage/app/email-*.html`.

## 8. Backup and restore (S7, X2)

Make a backup the way the server does each night at 01:00. Locally any archive password will do:

```sh
BACKUP_ARCHIVE_PASSWORD=local-test php artisan backup:run
ls storage/app/backups/ghm/
```

Then add a note to one of your cases, so you can tell the restore really happened, and follow `docs/restore.md` steps 1 to 8 with that zip and password. Locally the database host is `127.0.0.1`, and the user and database are both `ghm`. The restore replaces your local database with the backup's copy; that is only ever fake data.

| Try | Expect |
|---|---|
| Each step of `docs/restore.md` | Each command succeeds; step 3 prints `0` |
| Sign in, open the case you added the note to | The description reads normally and its documents download; the note is gone, because it came after the backup |
| Step 3 again with a wrong password | It prints `2` and unpacks nothing |

Step 6 caches the configuration, as a server should. On your machine, clear it afterwards, or the tests may read `.env`'s database settings instead of `ghm_testing`:

```sh
php artisan config:clear
rm storage/app/backups/ghm/*.zip
```

## 9. Automated checks

The same checks CI runs on every push:

```sh
php artisan test                                  # Pest; one requirement with --group=S2
vendor/bin/pint --test                            # code style
vendor/bin/phpstan analyse --memory-limit=1G      # static analysis
npx playwright test                               # browser tests; one requirement with --grep @S2
npx playwright test --ui                          # the same, in a window where you can watch each step
npx playwright show-report                        # after a failure: the report with screenshots and traces
```

Playwright starts its own servers on ports 8001 and 8002 and stops them afterwards; the full run takes about a minute and a half, because the S2 idle check waits out a one-minute session. Pest and Playwright both use the `ghm_testing` database, never `ghm`, so your demo data stays as you left it. Playwright signs in as fake users (`e2e-focal_person-0@example.com` and so on) that it creates there with fresh passwords, and enters fake cases, categories, locations and users; the next Pest run wipes them. Its saved sessions sit in `playwright/.auth/`; delete that folder to make it sign in afresh.

## 10. On a real phone (optional)

With the phone on the same Wi-Fi as the computer:

```sh
php artisan serve --host=0.0.0.0 --port=8000
ipconfig getifaddr en0          # macOS: this computer's address on the network
```

Open `http://<that address>:8000` on the phone. This makes the development server reachable from the local network, so only do it with fake data, and stop the server afterwards.

## Known so far

- The staff panel's title and the emails show `APP_NAME` ("Grievance handling" in `.env.example`) until the company name arrives at kick-off.
- The acknowledgement email's Nepali wording needs a native speaker's check (open question 3 in `docs/progress.md`).
- Backups stay on the same machine until the off-server copy is set up, once the host is known.
- The staff panel is in English until NBI answers question 14 in `docs/clarification-questions.md`; `APP_LOCALE=ne` stays the public pages' default.
- Categories and locations are placeholders until NBI sends the real lists; their Nepali names need a native speaker's check.
- The printed slip is in English and includes the staff instruction line. Its wording and language for the complainant are an open question (`docs/progress.md`).
- Notes, replies and documents are on the case page as tabs below the submission. The Administrator's menu has "Activity log": every case change, view, note, reply, document and sign-in, with who and when.
- The public home page is a placeholder until the WP1 grievance form.
