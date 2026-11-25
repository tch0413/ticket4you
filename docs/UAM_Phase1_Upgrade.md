# Tickets4You — User Account Management Phase 1

Focused update based on the user-uploaded `T4Y(1).zip`. This is **not** a full source replacement.

## Changes

- **Login throttling:** five failed attempts within 15 minutes lock the same login ID + portal for 15 minutes. A keyed hash (HMAC) is stored instead of raw login identifiers. Login counters clear on successful password verification. Existing role separation is unchanged.
- **Correct email change:** the verification link goes to the **proposed new email** via `external_mail`; a separate no-link warning goes to the **old email**. Verification rejects an already-claimed target address, closes outstanding change links and ends the old session.
- **Password security:** changing the current password invalidates previous sessions while preserving the session in which the password was changed.
- **Password reset requests:** repeated requests for an eligible account are throttled to approximately one reset token every ten minutes; the response remains generic.
- **Admin user interface:** search by name/email/Staff ID; filter by role/status; at most 100 matching rows. Uses parameterized database queries. Role-assignment selectors use a separate active-account list. New labels are translated in English/Malay/Simplified and Traditional Chinese.
- **Validation:** adding a role or event organiser now checks that the selected account is active.

## Apply safely to the existing GitHub workspace

1. In `C:\xampp\htdocs\T4Y`, run `git status`. Complete or stash any current work before switching. Keep untracked runtime/private files out of Git.
2. `git switch main`
3. `git pull --ff-only origin main`
4. `git switch -c feature/uam-phase1`
5. Download `Tickets4You_UAM_Phase1.patch`, then (in PowerShell, assuming Downloads):

   ```powershell
   git apply --check "$env:USERPROFILE\Downloads\Tickets4You_UAM_Phase1.patch"
   git apply "$env:USERPROFILE\Downloads\Tickets4You_UAM_Phase1.patch"
   ```

   If `--check` reports a conflict, **do not force it or overwrite teammates' changes**. Review the differences first.
6. **Back up the current MySQL database** before modifying its schema. Then install the one new table in the **same configured database** your app uses:

   ```powershell
   C:\xampp\php\php.exe scripts\migrate_uam_security.php
   ```

   `CREATE TABLE IF NOT EXISTS` makes this migration safe to rerun. It adds a single `auth_login_attempts` table; it does not reset user accounts.
7. Test:

   ```powershell
   C:\xampp\php\php.exe -l app\login_security.php
   C:\xampp\php\php.exe -l app\auth.php
   C:\xampp\php\php.exe -l modules\UserAccount.php
   C:\xampp\php\php.exe tests\uam_login_throttle_smoke.php
   C:\xampp\php\php.exe tests\uam_i18n_smoke.php
   C:\xampp\php\php.exe tests\uam_email_change_smoke.php
   C:\xampp\php\php.exe tests\role_login_smoke.php
   C:\xampp\php\php.exe tests\mysql_smoke.php
   ```
8. In the browser, test the Admin UAM page's search/filters, a **fictional test user's** five bad login attempts, and an email-change request. Run `C:\xampp\php\php.exe scripts\jobs.php`; verify the `external_*.txt` files in your **private** `storage/email_outbox` include one link **to the new address** and one notification **to the old address**. Visit the new-address verification link; verify the old email can no longer sign in.
9. Review and commit only the intended code files. Never include `storage/app.key`, `storage/email_outbox` or `.git` folder contents in shared ZIPs.

## Scope / limitations

- The login throttle is per **portal + login identifier**, not global IP-based bot prevention. This is a practical first-layer safeguard, not a substitute for WAF or production infrastructure protections.
- The existing mail queue writes **development outbox files**; it does not send live email without additional transport integration.
- The added Admin filters display up to 100 results and are not yet full pagination/export.
- Database-backed end-to-end UAM flows must be verified on the local XAMPP installation. The bundled UAM tests use in-memory mocks and do **not** prove the database migration is already installed or live email delivery is configured.
- Future UAM phases: accessible profile picture handling, explicit role revocation approvals, login history, pagination and stronger 2FA/security settings.
