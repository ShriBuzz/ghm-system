# Testing by hand

How to run the GHM system on your machine and try what it does so far, by hand and with the automated checks. Set up the machine first with `docs/local-setup.md`.

Use fake data only. Never point the app, a test or a browser session at production (`CLAUDE.md`).

Last updated 2026-11-01, at the WP2 proof-of-concept checkpoint (admin side up to offline entry). What each requirement covers so far is in `docs/requirements-traceability.md`.

## 1. Start fresh

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

## 7. Automated checks

The same checks CI runs on every push:

```sh
php artisan test                                  # Pest; one requirement with --group=S2
vendor/bin/pint --test                            # code style
vendor/bin/phpstan analyse --memory-limit=1G      # static analysis
npx playwright test                               # browser tests; one requirement with --grep @S2
npx playwright test --ui                          # the same, in a window where you can watch each step
npx playwright show-report                        # after a failure: the report with screenshots and traces
```

Playwright starts its own server on port 8001 and stops it afterwards. Pest and Playwright both use the `ghm_testing` database, never `ghm`, so your demo data stays as you left it. Playwright signs in as three fake users (`e2e-administrator@example.com` and so on) that it creates there with fresh passwords on every run, and enters fake cases; the next Pest run wipes them.

## 8. On a real phone (optional)

With the phone on the same Wi-Fi as the computer:

```sh
php artisan serve --host=0.0.0.0 --port=8000
ipconfig getifaddr en0          # macOS: this computer's address on the network
```

Open `http://<that address>:8000` on the phone. This makes the development server reachable from the local network, so only do it with fake data, and stop the server afterwards.

## Known so far

- The staff panel's title reads "Laravel" until the company name arrives at kick-off.
- The staff panel is in English until NBI answers question 14 in `docs/clarification-questions.md`; `APP_LOCALE=ne` stays the public pages' default.
- Categories and locations are placeholders until NBI sends the real lists; their Nepali names need a native speaker's check.
- The printed slip is in English and includes the staff instruction line. Its wording and language for the complainant are an open question (`docs/progress.md`).
- Not built yet: internal notes, replies and documents on a case (WP2 step 7) and the activity log (step 9).
- The public home page is a placeholder until the WP1 grievance form.
