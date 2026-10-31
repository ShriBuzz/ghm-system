# Local setup

How to get a working copy of the GHM system on a developer machine. macOS comes first; the Windows notes at the end record how the first machine was set up.

A local copy never points at production and never holds real grievance data (`CLAUDE.md`).

## What you need

| Tool | Version | Used for |
|---|---|---|
| PHP | 8.4 (8.3+ works; CI uses 8.4) | The app. Extensions: curl, fileinfo, intl, mbstring, openssl, pdo_mysql, sodium, zip |
| Composer | 2.x | PHP packages |
| MySQL | 8.x, or MariaDB 10.6+ | Local `ghm` and `ghm_testing` databases |
| Node | 20+ | Playwright and Impeccable only |
| Git and GitHub CLI | recent | Clone, push, pull requests |
| Claude Code | current | The coding agent, with the Ponytail plugin |

## macOS

### 1. Tools

Install [Homebrew](https://brew.sh) if it is not there yet, then:

```sh
xcode-select --install   # git and build tools; skip if already installed
brew install php@8.4 mysql@8.4 node gh
```

`php@8.4` and `mysql@8.4` are versioned formulas, so Homebrew does not put them on PATH. Add them and open a new terminal:

```sh
echo "export PATH=\"$(brew --prefix php@8.4)/bin:$(brew --prefix mysql@8.4)/bin:\$PATH\"" >> ~/.zshrc
```

Install Composer with its own installer. Homebrew's `composer` formula would pull in a second, newer PHP. This checks the installer's signature before running it:

```sh
EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
[ "$EXPECTED" = "$ACTUAL" ] && php composer-setup.php --install-dir="$(brew --prefix)/bin" --filename=composer
rm composer-setup.php
```

Check:

```sh
php -v       # PHP 8.4.x
php -m | grep -cE '^(curl|fileinfo|intl|mbstring|openssl|pdo_mysql|sodium|zip)$'   # 8
composer -V
node -v      # v20 or later
```

### 2. MySQL

```sh
brew services start mysql@8.4
mysql -u root      # Homebrew's MySQL root has no password by default
```

At the `mysql>` prompt, choose a password for the `ghm` user. It goes into `.env` only:

```sql
CREATE DATABASE ghm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE ghm_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ghm'@'localhost' IDENTIFIED BY 'choose-a-password';
CREATE USER 'ghm'@'127.0.0.1' IDENTIFIED BY 'choose-a-password';
GRANT ALL PRIVILEGES ON ghm.* TO 'ghm'@'localhost', 'ghm'@'127.0.0.1';
GRANT ALL PRIVILEGES ON ghm_testing.* TO 'ghm'@'localhost', 'ghm'@'127.0.0.1';
```

### 3. GitHub

```sh
gh auth login --hostname github.com --git-protocol https --web
gh auth setup-git
git config --global user.name "Your Name"
git config --global user.email "you@example.com"
```

### 4. The project

```sh
gh repo clone <owner>/ghm-system   # the same repository as origin on the first machine
cd ghm-system
git checkout wp0/foundations       # the branch in progress, see docs/progress.md
composer install
npm ci
npx playwright install chromium
cp .env.example .env
php artisan key:generate
```

`.env.example` already points at the local `ghm` database as the `ghm` user. Fill in the password you chose:

```dotenv
DB_PASSWORD=the-password-you-chose
```

Then:

```sh
php artisan migrate
php artisan serve        # http://127.0.0.1:8000
```

### 5. Agent tooling (once per machine)

- **Claude Code and Ponytail.** Start Claude Code in the project folder and trust the folder. `.claude/settings.json` names the Ponytail marketplace and enables the plugin, so Claude Code offers to install it. If it does not, run `/plugin marketplace add DietrichGebert/ponytail` and `/plugin install ponytail@ponytail` at project scope, then restart Claude Code.
- **Impeccable design hook.** It is kept out of git on purpose. Re-create it with the same version:

  ```sh
  npx impeccable@4.8.0 install --providers=claude --scope=project -y
  git status   # expect no changes; the hook lands in the ignored .claude/settings.local.json
  ```

  The macOS engine binary downloads on first use and is checked against `engine-checksums.txt`. If `git status` shows changed skill files, look at the diff before committing anything.
- **`/impeccable init`** is already done (`PRODUCT.md`). Do not run it again.

## Everyday commands

| What | Command | Works from |
|---|---|---|
| Run the app | `php artisan serve`, then http://127.0.0.1:8000 | now |
| Staff panel | http://127.0.0.1:8000/admin | WP0 step 4 |
| Code style | `vendor/bin/pint --test` | now |
| PHP tests | `php artisan test`, or one requirement with `php artisan test --group=A5` | now; Pest from step 10 |
| Static analysis | `vendor/bin/phpstan analyse` | step 10 |
| Browser tests | `npx playwright test`, or `npx playwright test --grep @smoke` | step 10 |
| Browser session per role | `npx playwright cli -s=focal open http://127.0.0.1:8000/admin` | step 4 |

## Windows (first machine)

How the Windows laptop was set up, for reference:

- PHP 8.4 (non-thread-safe) from windows.php.net through `winget install --id PHP.PHP.NTS.8.4 --scope user`. The zip ships without a `php.ini`: copy `php.ini-development` to `php.ini`, set `extension_dir` to the absolute `ext` path written with forward slashes, and enable curl, fileinfo, intl, mbstring, openssl, pdo_mysql, sodium and zip.
- Composer from the official installer (signature checked) in `%LOCALAPPDATA%\Programs\Composer`, with a `composer.bat` wrapper, added to the user PATH.
- GitHub CLI through `winget install --id GitHub.cli`.
- MySQL 8.0 as the `MySQL80` service. It starts manually: run `Start-Service MySQL80` in an admin PowerShell after a reboot.
- Open a new terminal after installing anything, so PATH changes apply.
