# Tickets4You (T4Y) — Integrated PHP / MySQL coursework prototype

**Based on the supplied Tickets4You Complete SRS v3.0 (19 November 2026).** This is an **integrated educational prototype**, not a production deployment or a claim of full SRS acceptance. Only SICC events may be created or booked. No real money is collected and no real payment-card data is processed.

## UI refresh for the existing `T4Y_Chatgpt\T4Y` installation (23 November 2026)

If you already installed Tickets4You at `C:\xampp\htdocs\T4Y_Chatgpt\T4Y`, **use the separate UI upgrade ZIP**, extracting its **contents into this existing T4Y directory**, and agree to replace the specified application files. Do **not** delete or overwrite your `storage/` folder, particularly `storage/app.key`, and do not reinstall or reset your existing MySQL database. Your existing database name remains `t4y_chatgpt_db`.

The UI refresh includes a navy/champagne editorial theme, reorganised responsive menus, one dropdown showing the **currently active** language, a searchable SICC Level 2–5 floor directory (40 names), and a capacity-required venue prefill link for administrators. The directory is **not** an architectural floor diagram: exact room locations, capacities, availability and adjacency were not supplied. It does not automatically add venues to the active database. A room is only added to operational venues after an admin enters its verified physical capacity.

No schema migration is required. The package also fixes the previous `localeCode()` undefined-session warning and sets development defaults for the nested XAMPP URL and the previously installed `t4y_chatgpt_db` database. If `T4Y_BASE_URL` is set in your Apache environment, update that override too. For a CLI test, run `C:\xampp\php\php.exe tests\ui_smoke.php` from the project folder.

See `docs/UI_UPGRADE.md` for the full application instructions.

## 1. Module ownership and files

| Student | Assessed CRUD module | File |
|---|---|---|
| TAN | User Account Management | `modules/UserAccount.php` |
| TAN | Venue Management | `modules/Venue.php` |
| JAY | Booking Management (including tickets/payment simulation) | `modules/Booking.php` |
| JAY | Category Management | `modules/Category.php` |
| IVAN | Customer Support & Disputes | `modules/Support.php` |
| IVAN | Event Management | `modules/Event.php` |
| YONG | Session Management | `modules/Session.php` |
| YONG | Staff Scheduling Management | `modules/Scheduling.php` |

Each file contains its own request handler and view rendering function. Shared code lives in `app/` and `shared/`. The four **proposed** unique features are separate files: `features/Rewards.php`, `features/RefundPolicy.php`, `features/Recommendations.php`, and `features/MFA.php`. Unique-feature tutor approval is still required under the SRS.

## 2. Requirements

- Windows with XAMPP, Apache, MySQL/MariaDB, PHP 8+ with **`pdo_mysql` and `openssl` enabled** (recommended PHP 8.2+)
- Web browser; Bootstrap CSS/JS is bundled locally in the UI-refresh package. QR display currently uses an optional external CDN.
- VS Code recommended for reviewing files
- Workspace: `C:\xampp\htdocs\T4Y_Chatgpt\T4Y`
- Database: `t4y_chatgpt_db`

## 3. First-time installation (PowerShell on Windows)

1. For a fresh install, extract the archive so that `C:\xampp\htdocs\T4Y_Chatgpt\T4Y\index.php` exists. Do not accidentally create a third nested `T4Y` directory.
2. Start **Apache and MySQL** in XAMPP Control Panel. Ensure that the MySQL server user can create the `t4y_chatgpt_db` database.
3. Set **your own** initial administrator credentials before running the installer:

   ```powershell
   cd C:\xampp\htdocs\T4Y_Chatgpt\T4Y
   $env:T4Y_ADMIN_EMAIL = "admin@example.test"
   $env:T4Y_ADMIN_PASSWORD = "ChangeThisDemo2026!"
   C:\xampp\php\php.exe scripts\install.php
   C:\xampp\php\php.exe scripts\demo_seed.php
   C:\xampp\php\php.exe scripts\jobs.php
   ```

   The example password above is for **local demonstration only**. Replace it with your own secret and do not use it for real accounts. The installer **does not print the administrator password**. It generates `storage/app.key`, a secret used for QR signatures and encrypted MFA secrets. **Never delete it after issuing tickets or enrolling MFA.**

4. Open **http://localhost/T4Y_Chatgpt/T4Y/index.php**. Sign in with the admin email/password used at installation. The optional seed provides a **fictional** SICC technology event, one session, and three categories. Demo capacity numbers are **not actual SICC venue capacities**.
5. To book as a customer: register another account, execute `scripts/jobs.php`, open the private development outbox under `storage/email_outbox/`, follow that account's verification link, sign in, select the fictional event, reserve tickets, then simulate payment.

### Database / server overrides

| Environment variable | Default (XAMPP local development) |
|---|---|
| `T4Y_DB_HOST` | `127.0.0.1` |
| `T4Y_DB_PORT` | `3306` |
| `T4Y_DB_NAME` | `t4y_chatgpt_db` |
| `T4Y_DB_USER` | `root` |
| `T4Y_DB_PASS` | empty (XAMPP only) |
| `T4Y_BASE_URL` | `http://localhost/T4Y_Chatgpt/T4Y/index.php` |
| `T4Y_EMAIL_FORCE_FAIL` | `0` (set `1` to test retry queue) |
| `T4Y_DEBUG` | `0` (avoid showing internal errors) |

**Security note:** All application PHP files beyond the entrypoint are blocked by the included `.htaccess`. Confirm Apache `AllowOverride`/rewrite protections. For live hosting the web root should be isolated to an explicit public directory; do not deploy private storage, setup scripts, or demo user data on a publicly reachable server. Restrict directory permissions and HTTPS-enable any real deployment.

## 4. Navigating the integrated workflow

1. **User Account Management:** register, verify email, log in, update profile/password, request verified email change, create staff and set staff categories. An administrator grants event-scoped organiser access. Staff temporary passwords are displayed **once to the administrator** and must be sent privately; the staff member must change their password at first login. Admin can revoke event access immediately.
2. **Venue Management:** CRUD for SICC spaces, layouts and maintenance blocks. Capacity reductions and overlapping reservations are checked.
3. **Event Management:** a verified customer submits a proposal; Admin reviews and approves. Approval creates a **Draft** event, the first scheduled session and an organiser assignment. Event visibility and publication remain Admin-controlled.
4. **Session Management:** create/update/archive session dates, approved capacities, SICC venue/layout assignment and setup/teardown buffers. Every event session has independent capacity.
5. **Category Management:** create/update/archive paid/public-free/complimentary ticket categories within session allocation; prices are locked after sales begin.
6. **Booking Management:** a verified customer selects one session and ticket quantities. Reservation holds inventory for five minutes by default (configurable by event). The user simulates FPX/card/e-wallet success/failure; success issues **one signed unique ticket per unit**. Payment attempts, receipts, personal booking history and formal transfer are integrated. Admin can issue complimentary tickets to verified customers.
7. **Customer Support:** customers open cases and request refunds per individual paid ticket. The system freezes the submitted estimate and marks tickets under review. Admin or event-authorised Finance staff approves/rejects; Booking records the **separate** simulated settlement state and only voids tickets on successful refund.
8. **Staff Scheduling:** Admin creates/updates/cancels event/session shifts. The system prevents staff overlaps, including overnight periods. The shared QR gate checks current entrance permission and active shift with a 30-minute grace period.

The Home page shows public event listings; the Dashboard shows role-appropriate views. Note that a QR code graphic uses an optional client-side CDN library: the **signed ticket payload** and keyboard-compatible staff scanning form remain usable without it.

## 5. Four distinct extra-feature sources

- **TAN Customer Rewards:** ledger-based loyalty points, a single promo/points/bundle discount per booking, same-session bundle components and transaction-checked promo limits. Configurable demo conversion values are **proposed** and not real SICC policy.
- **JAY Refund Policy Interface:** Admin can publish immutable versions. Version precedence is category > event > default. Default exact boundaries: `>=31d 100%`, `>=16d 50%`, `>=8d 25%`, `<8d 0%`. Only a completed simulated refund voids a ticket; cancelled events use the full paid-price-and-fees exception.
- **IVAN Personalised Event Recommendations:** deterministic public-event ranking; individual category preferences, favourites and prior paid-booking interests influence ranking **only with explicit opt-in**. Promotional email opt-in is separate.
- **YONG MFA:** encrypted TOTP setup, 6-digit authenticator validation, pending-MFA login, per-factor replay prevention, and hashed single-use recovery codes. Admin MFA can remain password-only until the Admin enrolls, per prototype baseline. No SMS dependency.

## 6. Queue and job execution

Business transactions do **not** depend on mail success. Email goes into a **private local outbox**, not a live SMTP system. Run this command after account registration, booking or support actions to deliver queued messages:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\T4Y_Chatgpt\T4Y\scripts\jobs.php
```

For repeated automatic delivery and expired-hold cleanup, create a Windows Task Scheduler task running that command **every minute**. Retry delays after failures are 1, 5, 15 and 60 minutes. Unpaid holds are also ignored by inventory calculations immediately after expiry, even before the job runs.

## 7. Verification

```powershell
C:\xampp\php\php.exe tests\unit.php
C:\xampp\php\php.exe tests\mysql_smoke.php
C:\xampp\php\php.exe -l index.php
```

`tests/unit.php` checks money allocation, tier boundaries and RFC TOTP vector without requiring a database. `mysql_smoke.php` checks table creation, verified Admin and policy boundaries on **your** live local MySQL database. See `docs/MANUAL_TESTS.md` for integrated CRUD and edge-case scenarios.

**Verification performed for this archive:** all PHP files passed PHP 8.4 syntax validation; pure unit tests passed; the simple PDF export renderer produced a valid one-page A4 PDF. **Not performed here:** an end-to-end MySQL/XAMPP integration execution, because the build environment has no `pdo_mysql` driver or running MySQL/MariaDB server. Run `mysql_smoke.php` and the manual tests on XAMPP before coursework submission.

## 8. Known differences from the full SRS v3.0

This is **not a claim of 100% production readiness or complete SRS conformance**. `docs/SRS_COVERAGE_AND_GAPS.md` records remaining work, especially advanced seating, full multilingual UI, complete translation workflows, offline gate contingency, notification template editor, SMTP integration, refund compensation after earlier partial payouts, reports/charts, late provider callback resolution, advanced permissions/approvals, backup/restore automation, full UAT and concurrency stress testing. No live payment, user dataset, or official SICC capacity information is included.

## 9. Project structure

```text
T4Y/
  index.php                      Main entrypoint and route integration
  config.php                     Environment-driven settings
  app/                           Database, authentication, business rules, layout, locales
  modules/                       Eight separate assessed module PHP files
  features/                      Four unique-feature PHP files
  shared/                        Dashboards, QR/check-in, notifications, PDF export
  database/schema.sql            All foreign-keyed MySQL data tables
  scripts/install.php            DB creation + secure Admin bootstrap
  scripts/demo_seed.php          Fictional event and ticket categories
  scripts/jobs.php               Outbox delivery, retries, hold cleanup, reminders
  assets/                        CSS, browser JS
  storage/email_outbox/          Private runtime demo email, not committed
  tests/                         Unit tests, MySQL smoke tests
  docs/                          SRS coverage and human test instructions
```
