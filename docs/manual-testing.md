# Testing by hand

How to run the GHM system on your machine and try what it does so far, by hand and with the automated checks. Set up the machine first with `docs/local-setup.md`.

Use fake data only. Never point the app, a test or a browser session at production (`CLAUDE.md`).

Last updated 2026-10-31, at the end of WP0. What each requirement covers so far is in `docs/requirements-traceability.md`.

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

## 5. The other roles

There is no user-management screen until WP2, so create the focal person and the restricted-case officer from the terminal, with fake details:

```sh
php artisan tinker --execute 'App\Models\User::create(["name" => "Test Focal", "email" => "focal@example.com", "password" => "pick-a-password", "role" => App\Enums\Role::FocalPerson]);'
php artisan tinker --execute 'App\Models\User::create(["name" => "Test Officer", "email" => "officer@example.com", "password" => "pick-a-password", "role" => App\Enums\Role::RestrictedOfficer]);'
```

Sign in as each one in a private window or another browser, so the sessions stay apart. Each account sets up its own two-factor login at first sign-in. Every role sees only the dashboard until WP2 adds the case screens; which cases each role may see is covered by the automated tests (`php artisan test --group=A5`).

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

Playwright starts its own server on port 8001 and stops it afterwards. Pest uses the `ghm_testing` database, never `ghm`.

## 8. On a real phone (optional)

With the phone on the same Wi-Fi as the computer:

```sh
php artisan serve --host=0.0.0.0 --port=8000
ipconfig getifaddr en0          # macOS: this computer's address on the network
```

Open `http://<that address>:8000` on the phone. This makes the development server reachable from the local network, so only do it with fake data, and stop the server afterwards.

## Known at the end of WP0

- The staff panel's title reads "Laravel" until the company name arrives at kick-off.
- The staff panel is in Nepali because it follows the app's default language, `APP_LOCALE=ne`. The public pages ignore that setting and take their language from the address (`/ne`, `/en`); the panel has no switch of its own yet, because which language staff get is question 14 in `docs/clarification-questions.md`. Filament's Nepali translation is incomplete, so some staff screens mix Nepali and English.
- To see the staff panel in English locally, set `APP_LOCALE=en` in `.env`. The public pages keep their own addresses; the only side effect is that a first visit to `/` then goes to `/en` instead of `/ne`. Do not set it in production while public pages are meant to open in Nepali.
- The public home page is a placeholder until the WP1 grievance form.
