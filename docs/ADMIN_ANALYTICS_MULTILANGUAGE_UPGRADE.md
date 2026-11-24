# Tickets4You — Admin Charts & Multilingual UI Upgrade

## Install into an existing XAMPP project

**Workspace:** `C:\xampp\htdocs\T4Y`  
**URL:** `http://localhost/T4Y/index.php`

1. Back up your existing `T4Y` folder and export your existing MySQL database in phpMyAdmin.
2. Extract the **Admin Analytics & Language Upgrade ZIP**. Copy the files and directories inside the archive directly into `C:\xampp\htdocs\T4Y`, replacing matching files. Do not create another nested `T4Y` directory.
3. Keep your existing `config.php`, `storage/app.key`, database, accounts, events, bookings, and tickets. This patch does **not** contain `config.php`, `storage`, SQL migrations, or an installer.
4. Restart Apache if needed and use `Ctrl + F5` to refresh the browser.
5. Sign in through the Administrator portal and visit `http://localhost/T4Y/index.php?page=dashboard`.

## Features

- Admin KPI, financial labels and tables translated into English, Bahasa Melayu, Simplified Chinese, and Traditional Chinese.
- Additional shared translations for common management form fields, table headings, buttons and status badges. Some legacy module-specific paragraphs and free-form database content still use their source language until supplied with approved translations.
- Five **time-series** charts: gross revenue, paid bookings, historical paid tickets issued, completed refunds, and unique first admissions.
- Three **breakdown** charts: revenue by event, ticket category sales, and booking status distribution.
- Click a chart or select **View details** to open an admin-only detailed chart and its underlying aggregate table.
- Time filters: 7 days, 30 days, 90 days, 12 months, or custom dates (up to 730 days), plus optional event filter.
- Date filters are bound SQL parameters, not concatenated user input. Locale switching preserves the current chart and range.
- No third-party chart service: graphs use locally rendered SVG.
- Dates are interpreted in `Asia/Kuching` (+08:00). Revenue is counted on payment confirmation date; refunds on processed date; ticket issuance on issued date; admission on check-in date; order-status distribution on order creation date.
- No fake analytics: zero actual bookings means flat zero charts.

## Test

Run from a VS Code PowerShell terminal:

```powershell
cd C:\xampp\htdocs\T4Y
C:\xampp\php\php.exe tests\admin_analytics_smoke.php
C:\xampp\php\php.exe tests\mysql_smoke.php
```

The first test covers date-bucket counts, range validation, SVG rendering, translations and route integration. The second checks the database. Full live MySQL analytics cannot be tested until the update runs on your local XAMPP environment.

## Notes

- The Admin dashboard is restricted to the authenticated Admin role. The new analytics details page is also Admin-only.
- Aggregates are read-only: no new tables or modifications to ticket inventory, revenue, payments, or existing data.
- The current 30-second page reload was removed from the Admin dashboard to avoid discarding unsaved date filter selections. Other dashboards continue to refresh as previously configured. A live-polling enhancement can be added later.
- Event titles/descriptions remain in their approved original language unless approved event translations exist in `event_translations`.
