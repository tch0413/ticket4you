# EventSphere: shared database integration patch

## Scope and limitations
This is an incremental patch for the uploaded `T4Y (2)(1).zip` working tree, not a full EventSphere SRS v2.0 implementation. The old PHP modules remain compatible with their existing 47-table schema. Database name defaults to `eventsphere_db`, the connection follows the lecturer's PDO/environment-variable pattern, and all roles now have a 60-minute inactivity timeout. The initial Admin installer uses a 12-character complexity rule. Existing registration/recovery/role rules and UI are still from T4Y; one shared login interface, Organizer usernames, separate passwords, shared email support, progressive lockout, registration fields and other SRS changes need separate coordinated migrations and implementation. Customer rewards and remaining shared SRS decisions are not finalized.

No real server, credentials, data dump or app key are included. This package does not deploy or synchronize a real database. The ZIP does not include `.git`, runtime storage, personal data or private credentials. It is designed to overlay the existing repository, not replace it.

## 1. Protect existing work
1. Ask everyone to commit or stash their changes. Pull the approved `main` and create a new feature branch, e.g. `feature/eventsphere-shared-db`.
2. Back up the current database privately using phpMyAdmin export; save the original `storage/app.key` separately in a restricted location. Do not commit backups or keys. Never upload actual personal data to the test database. Use fictional test records.
3. Compare the patch with your branch before copying its contents into `C:\xampp\htdocs\T4Y` (the existing project folder). Do not overwrite your teammates' unmerged changes. If your folder differs, use its actual path. The existing folder does not need renaming.
4. Copy `config/database.example.php` to `config/database.local.php`. The latter is ignored by Git. Edit it locally, and keep Apache's `AllowOverride` and rewrite protections enabled. Prefer OS environment variables and secrets outside htdocs for sensitive deployments. Do not use the prototype as an Internet-facing website.
5. Environment variables `DB_MODE`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_SSL_CA`, `APP_KEY_FILE` override the private file. Old `T4Y_DB_*` overrides are no longer read. `T4Y_BASE_URL`, `T4Y_ADMIN_EMAIL` and `T4Y_ADMIN_PASSWORD` retain their existing names. Check for stale environment overrides in Apache and PowerShell. Restart Apache if its environment changes.

## 2. Local development (each member)
With `DB_MODE=local` and the default `127.0.0.1`, each PC has an independent database. This is intentional for isolated branch development. Existing databases are not automatically renamed or imported. To temporarily preserve access to an older local database set DB_NAME to its actual name, for example `t4y_chatgpt_db`, and leave the original key in place. To initialize a fresh local `eventsphere_db`, start Apache and MySQL and run in PowerShell:

```powershell
cd C:\xampp\htdocs\T4Y
$env:T4Y_ADMIN_EMAIL = 'admin@example.test'
$env:T4Y_ADMIN_PASSWORD = Read-Host 'Enter a new strong fictional Admin password'
& C:\xampp\php\php.exe scripts\install.php
Remove-Item Env:T4Y_ADMIN_PASSWORD
& C:\xampp\php\php.exe scripts\db_status.php
& C:\xampp\php\php.exe tests\mysql_smoke.php
```
The existing Admin portal still uses email/password in this patch. The installer does not reset an existing Admin password or change an existing non-Admin account into an Admin. The existing demo seed scripts may require a demo venue and are not run automatically.

## 3. Shared integration database (set up once)
Choose a managed MySQL/MariaDB database or a college/team server reachable over a private network/VPN. Ask the lecturer or database administrator for a TLS-enabled server and a database hostname matching its certificate. Do not expose a laptop's XAMPP MySQL port 3306 to the public Internet, use a remote root account or disable certificate verification. A local-only connection or four identical database names on different computers will NOT share records.

The database administrator creates the database `eventsphere_db` with utf8mb4 and InnoDB support (MySQL 8 or MariaDB 10.4+ compatible with the existing project). Issue individual password-protected accounts with only SELECT, INSERT, UPDATE, DELETE on this database for the four runtime applications. Use a separate authorized migration account for schema setup and subsequent reviewed changes (CREATE, ALTER, INDEX, REFERENCES as required plus DML; no global privileges). Require TLS at the server, restrict network access, and maintain backups. All development records must be fictional. This is not production-grade authorization between developers: DB credentials allow direct access to shared test records.

For a new shared database, generate a new 32-byte base64 key once on a trusted machine (PHP CLI):

```powershell
& C:\xampp\php\php.exe -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'
```
Save that output privately in a key file outside htdocs; distribute the same file using a secure channel, not GitHub/chat. If restoring encrypted/signed records from an existing test database, retain the matching original key instead; rotating it without a coordinated migration breaks MFA/QR signatures and other protected values. For isolated local development, use a different key. Never include the key in a submission ZIP or logs.

Each member edits their own `config/database.local.php` with the same shared host, port and database, their own database username and password, trusted CA certificate path and identical private key contents. Example values are placeholders, not a real host:

```php
return [
    'DB_MODE' => 'shared',
    'DB_HOST' => 'your-database-host.example',
    'DB_PORT' => '3306',
    'DB_NAME' => 'eventsphere_db',
    'DB_USER' => 'your_assigned_db_user',
    'DB_PASSWORD' => 'your_private_db_password',
    'DB_SSL_CA' => 'C:/EventSphere-private/server-ca.pem',
    'APP_KEY_FILE' => 'C:/EventSphere-private/app.key',
    'T4Y_BASE_URL' => 'http://localhost/T4Y/index.php',
];
```
Prepend `<?php` if creating the file from scratch. Host, port and CA come from your database administrator. Paths are examples and must exist; forward slashes work on Windows. The runtime checks for encryption and rejects remote root, empty shared password, missing key or CA. Certificate validity is checked by PDO's MySQL driver when connecting. A sample configuration validation test does not verify a real TLS connection.

One designated migration owner runs `scripts/install.php` once on a new shared database using the migration account and a strong fictional initial Admin password (as above). For an existing compatible test-data export, select the target database and import the export without old `CREATE DATABASE` or `USE` statements; review the exact source schema and data on an isolated copy first, then run `scripts/migrate.php`. The baseline creates missing tables, not missing columns on existing tables. It is not a conversion from a different schema. Preserve the matching key; avoid overwriting an existing populated target. After setup, the migration owner switches back to a runtime account. Other members do NOT run install, import or seed against the shared database. Run `scripts/db_status.php` on all four machines.

## 4. Schema updates with GitHub
Every account or booking written by the application is committed to the central database immediately; no Git operation is required for record updates. Git tracks schema code only. The baseline `database/schema.sql` is target-neutral and includes all 47 legacy tables. `scripts/migrate.php` records migration name, normalized SHA-256 checksum and status in `schema_migrations`. A database advisory lock prevents concurrent migration runs. Applied files are skipped and edited/missing/out-of-order files are rejected. Future migration files belong in `database/migrations/` with a unique increasing number such as `0001_add_example_field.sql`, one ordinary SQL statement per file. Do not edit the baseline or applied migrations. Review each migration against the current schema in a disposable local copy first. Prefer additive changes and avoid incompatible schema changes while older application branches are connected.

Workflow: developer adds a reviewed migration alongside code -> test locally -> pull request -> merge -> designated owner backs up shared DB and runs:

```powershell
& C:\xampp\php\php.exe scripts\migrate.php
& C:\xampp\php\php.exe scripts\db_status.php
```

Everyone pulls the same approved code version. Run migrations again on each independent local database if needed. Run schema changes only with the migration account, never automatically on an HTTP request or every login. The runner cannot roll back MySQL DDL atomically or verify arbitrary manual schema drift. In case of failure, stop integration tests, inspect the partial changes and restore/repair with the database administrator. `--retry-failed` is only for a reviewed safe retry; it must not be used blindly. Keep a backup and record the tested database version. Do not run stale original migration scripts from old branches against the shared DB.

## 5. Verification checklist
- Each PC runs `scripts/db_status.php`; check configured mode/host/port, actual server, database name and applied migration list match. The database usernames may differ.
- Tan registers a fictional Customer with a unique email. Jay checks the account count or authorized Admin account listing (refresh the page); it should show the new record without Git push/pull. Use a distinct test account for each member, since the old app allows one active session per account and another login invalidates the previous session.
- Jay updates an eligible test record and Ivan checks it from his laptop. Yong checks the result after pulling compatible code.
- Migration owner runs the migration script twice; the second run should skip already applied migrations. Verify existing counts/records are unchanged and review application tests.
- Confirm access to config, storage and scripts over HTTP is blocked (403). Do not share passwords, CA private keys, personal data, password hashes or `storage/app.key` in the repository.
- Email delivery remains a local simulated outbox in this legacy app. Run the shared queue worker on ONE designated machine only, not all four, and use fictional addresses. File uploads, PHP sessions and outbox files are still local; real shared asset storage/SMTP require separate implementation. Existing static images should be synchronized through Git.

## Tests performed in the build environment
Run `php tests/shared_database_config_unit.php`, `php tests/unit.php`, `php tests/jay_booking_unit.php` and PHP syntax checks. No PDO MySQL driver or database server is available in the build environment. The actual install, migration execution, remote TLS verification and cross-PC registration test must be performed by the team using XAMPP and the provisioned server.
