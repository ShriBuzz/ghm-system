# Restoring from a backup (S7)

For whoever restores the grievance system after data loss. Try it on staging first; the timed drill before go-live follows these steps on staging with fake data.

Last tried end to end: locally on 2026-11-02 with fake data (steps 1 to 6 took 12 seconds for a 9 KB backup). On staging: not yet.

## What a backup holds

- One encrypted zip per night, made at 01:00 Nepal time, in `storage/app/backups/ghm/` (named `YYYY-MM-DD-HH-MM-SS.zip`), plus any off-server copy listed in `BACKUP_DISKS`.
- Inside: `db-dumps/mysql-<database>.sql` (the whole database) and `attachments/` (the uploaded files).
- Kept 15 days; older ones are deleted at 01:30.
- Not inside: the code (keep the release zip of each version you deploy) and `.env`, which holds the keys and is kept apart.

## What you need

1. The archive password (`BACKUP_ARCHIVE_PASSWORD`), from the company's password manager.
2. The `APP_KEY` the site used when the backup was made, also from the password manager. Without it, the encrypted fields cannot be read: names, contacts, descriptions, the optional answers, messages, notes, file names and staff two-factor secrets.
3. SSH access to the server and the database credentials from `.env`.
4. Only if the server itself is lost: the release zip of the version the site ran.

## Steps

Run these from the application folder on the server.

1. Stop the public form and staff panel while you restore:

   ```sh
   php artisan down
   ```

2. Choose the backup, usually the newest:

   ```sh
   ls storage/app/backups/ghm/
   ```

3. Unpack it into a folder of its own. The password is typed, not put on the command line, so it stays out of the shell history:

   ```sh
   read -s -p "Archive password: " BACKUP_ARCHIVE_PASSWORD; echo; export BACKUP_ARCHIVE_PASSWORD
   mkdir -p ~/restore
   php -r '$z = new ZipArchive; $z->open($argv[1]) === true || exit(1); $z->setPassword(getenv("BACKUP_ARCHIVE_PASSWORD")); exit($z->extractTo($argv[2]) ? 0 : 2);' storage/app/backups/ghm/<file>.zip ~/restore
   echo $?
   unset BACKUP_ARCHIVE_PASSWORD
   ```

   `0` means it worked. `2` means the password is wrong, and nothing was unpacked. `1` means the zip could not be opened: check its name.

4. Load the database. This replaces every table with the backup's copy:

   ```sh
   mysql -h <DB_HOST> -u <DB_USERNAME> -p <DB_DATABASE> < ~/restore/db-dumps/mysql-<database>.sql
   ```

5. Put the attachments back:

   ```sh
   mkdir -p storage/app/private/attachments
   cp -R ~/restore/attachments/. storage/app/private/attachments/
   ```

6. Check that `.env` has the `APP_KEY` from step 2 of "What you need". If the key was changed after the backup, put the backup's key in `APP_KEY` or `APP_PREVIOUS_KEYS`. Then bring the tables up to the installed version (a backup made before an update lacks its new columns; otherwise it prints "Nothing to migrate"), and open the site:

   ```sh
   php artisan migrate --force
   php artisan config:cache
   php artisan up
   ```

7. Check it worked: sign in as the Administrator, open a case and read its description, and download one of its documents.

8. Delete the unpacked copy, which holds the data unencrypted:

   ```sh
   rm -rf ~/restore
   ```

Cases and messages received after the backup was made are lost. Tell NBI and the company which day was restored.

## If the server itself is lost

The steps above use the copy on the server. When the server is gone, its copy is gone too, so this needs the off-server copy. **None exists yet** (`docs/technical-reference.md`, Backups): until one is built, a lost server means the data since the last copy kept elsewhere is lost.

With an off-server copy:

1. Set up the new server with `docs/deploy.md` sections 1 to 4, from the release zip of the version the site ran.
2. Follow section 5 with three differences: put the old `APP_KEY` from the password manager in `.env` instead of running `php artisan key:generate`; leave the `GHM_ADMIN_*` lines out; and skip `php artisan migrate` and `php artisan db:seed`, because the backup brings the tables, the users, the categories and the locations.
3. Copy the newest archive from the off-server copy into `storage/app/backups/ghm/`.
4. Follow the steps above from step 1. Step 4 creates the tables in the empty database.
