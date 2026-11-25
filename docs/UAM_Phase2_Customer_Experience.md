# Tickets4You — UAM Phase 2 / customer-experience upgrade

This patch is based on the user's T4Y(1).zip with the already supplied **UAM Phase 1 patch** applied. It is meant for the active `feature/uam-phase1` branch, **not** a wholesale replacement of `C:\xampp\htdocs\T4Y`.

## Scope and ownership
- UAM: registration choices, verified-account profile changes, password email OTP, email ownership verification, hover profile menu, account tabs, consent-based analytics.
- Event Application UI: explicit Confirm/Clear/Close date selector, structured staff categories and quantities, active configured venues with on-page image/details preview, verified-email guidance, navigation position. This touches `modules/Event.php`; coordinate the Event-module owner before merging.
- No changes to booking, payment, refund handling, event approval, venue locking or ticket inventory.

## What changed
1. Date/time selection uses an in-page modal with **Confirm selection**, **Clear**, **Cancel** and **X**. Server still validates start/end.
2. Staff plan replaces free text with selectable Entrance, Booking, Finance and Event Management categories, each with a quantity 1–100. The existing `staffing_plan` VARCHAR is retained using formatted text for backwards compatibility. Future per-category reporting would benefit from a dedicated relationship table.
3. Account page uses tabs; personal information requires current password; password changes require the current password followed by a six-digit email OTP (10-minute expiry, five attempts, 60-second resend cooldown); successful change revokes other login sessions.
4. Email change uses the current password and automatic **new-address ownership verification** (from Phase 1). There is no administrative approval, but the email does not change before the new address is verified.
5. Profile dropdown opens under avatar on hover with a keyboard/click fallback and relevant links.
6. Event applications moved from Account & Help to Main Menu. Active configured venues are displayed with imagery and details; **active does not guarantee date availability**, and final allocation remains with Admin. Only database-configured active venues (not all 40 static Floor Directory entries) can be selected for an application.
7. Additional registration information is strictly **optional**: age band, gender, region, occupation, interests. Separate unchecked analytics consent is required before saving preferences, and consent is not collected for under-18 age selections. Users can withdraw consent in My Account, which deletes stored preferences.

## Installation (Windows / VS Code PowerShell)
1. Back up the **active `t4y_chatgpt_db`** via phpMyAdmin. Preserve existing app key and local database.
2. Ensure Phase 1 patch and migration were already installed and tests passed.
3. `cd C:\xampp\htdocs\T4Y`
4. `git status` and ensure you are on the intended feature branch with only known changes.
5. Extract this ZIP **to Downloads**, not over the project. The patch file should be in Downloads or the extracted subfolder.
6. `git apply --check "C:\Users\cheeh\Downloads\Tickets4You_UAM_Phase2.patch"` (adjust the extracted location). If errors appear, stop; never use `--force`.
7. `git apply "C:\Users\cheeh\Downloads\Tickets4You_UAM_Phase2.patch"`
8. Review: `git status` and `git diff --check`
9. Confirm CLI database: `C:\xampp\php\php.exe -r "require 'app/core.php'; echo db()->query('SELECT DATABASE()')->fetchColumn(), PHP_EOL;"` — must display `t4y_chatgpt_db`.
10. Run **once, idempotent**: `C:\xampp\php\php.exe scripts\migrate_uam_phase2.php`
11. Run tests (below), then reload website with Ctrl+F5 and test new forms using an ordinary test customer. Avoid changing your only admin credentials.

## Tests
```powershell
C:\xampp\php\php.exe tests\uam_phase2_smoke.php
C:\xampp\php\php.exe tests\uam_password_otp_smoke.php
C:\xampp\php\php.exe tests\uam_phase2_i18n_smoke.php
C:\xampp\php\php.exe tests\event_application_phase2_smoke.php
C:\xampp\php\php.exe tests\uam_login_throttle_smoke.php
C:\xampp\php\php.exe tests\uam_email_change_smoke.php
C:\xampp\php\php.exe tests\role_login_smoke.php
C:\xampp\php\php.exe tests\venue_gallery_smoke.php
C:\xampp\php\php.exe tests\mysql_smoke.php
```

## Email delivery in this prototype
The OTP is queued in the `notifications` table. Run `C:\xampp\php\php.exe scripts\jobs.php` to deliver development emails into `storage\email_outbox`; do not publish or commit OTP messages. This is a **local prototype**, not actual SMTP delivery. Ensure genuine email delivery and anti-abuse protections before any production deployment.

## Required browser checks
- Customer profile: personal details require current password; OTP invalid, expired or already-used codes are rejected; after valid OTP, previous sessions are invalid.
- Email: entering current password queues verification to **new** email; old email remains valid until clicked; current session is revoked after verification.
- Registration: without consent, optional fields are not stored; with consent, validated adult selections are stored; withdrawing consent deletes them.
- Events: an unverified account sees instructions and cannot submit; a verified account can choose a date/time through the modal, select staff categories and quantities, view venue photos/details inside the page, and submit an application.
- Admin workflow: staff and organiser assignment, venue capacity and event approval remain unchanged.

## Additional cautions
- Screenshots or PDFs with one-time staff credentials should not be shared publicly. Rotate any credential exposed beyond its intended recipient.
- This patch uses an opt-in age **band**, not exact birthdate, to reduce unnecessary personal data collection. If specific events later require an age check, handle that separately and restrict access.
- The extra event form work should be reviewed by the person responsible for the Event Management module before merging into the team's `main` branch.
