# Tickets4You — Five Demo Events and Customer UX Upgrade

Project root: `C:\xampp\htdocs\T4Y`  
Local URL: `http://localhost/T4Y/index.php`

## What this adds

- Five **fictional** published SICC events, based on supplied SICC Level 2–5 venue names.
- Seven dated demonstration sessions across the five events, each with independently allocated tickets.
- SICC event cards with location/floor, live configured price range, available public category names, **View details** and **Book now** actions.
- Public event-detail route at `?page=event_details&event_id=<id>` showing full description, sessions, category prices, live remaining quantity and the link to existing Booking Management checkout.
- An accessible sticky (independently scrollable) customer sidebar on desktop. The mobile collapse behaviour remains.
- Consistent sidebar layout on dashboard, bookings, tickets, saved events, rewards, support, event details, account/profile, MFA and other customer navigation pages.
- Existing bookings and ticket pages retain friendly empty states with links to explore events. The rewards page now does too.
- New English/Bahasa Melayu/Simplified Chinese/Traditional Chinese translations for common *new* interface strings; fictional event content remains in the originally supplied language until an approved translation is entered.

## Installation: existing system (recommended)

1. Back up the existing `C:\xampp\htdocs\T4Y` folder **and its MySQL database**.
2. Extract the **upgrade ZIP** into `C:\xampp\htdocs\T4Y` directly, replacing matching files, **not** into `C:\xampp\htdocs\T4Y\T4Y`.
3. Keep your existing `storage/`, `storage/app.key`, database, `config.php`, `app/core.php` and existing accounts. The upgrade archive includes none of these.
4. Start Apache and MySQL in XAMPP.
5. Open PowerShell and run:

   ```powershell
   cd C:\xampp\htdocs\T4Y
   C:\xampp\php\php.exe scripts\demo_five_events.php
   C:\xampp\php\php.exe tests\event_expansion_smoke.php
   C:\xampp\php\php.exe tests\event_details_smoke.php
   C:\xampp\php\php.exe tests\customer_portal_smoke.php
   C:\xampp\php\php.exe tests\mysql_smoke.php
   ```

6. Open `http://localhost/T4Y/index.php?page=dashboard` with a **Customer portal** login and press `Ctrl+F5`.

**Do not rerun `scripts/install.php`.** The seed script is **idempotent by event title**: repeated runs skip existing demo events rather than duplicating them. No schema change is required.

## Fictional events and assigned SICC directory spaces

| Fictional demo event | SICC directory venue | Level | Public category names |
|---|---|---|---|
| Sabah Innovation & Robotics Expo | Kinabatangan I | 2 | Standard, Student, Free Community Pass |
| Borneo Creative Market & Makers Fair | Jesselton Square (Open Plaza) | 3 | General Admission, Youth Entry |
| Sabah Performing Arts Evening | Sipadan I-III | 4 | Standard, Premium |
| Sabah Digital Creators Conference | Kadamaian | 5 | Conference Pass, Student Pass |
| Sustainable Sabah Community Forum | Mesilau | 5 | Free Community Entry, Supporter Entry |

The seed chooses future local dates relative to the day of execution (starting 25, 32, 39, 46 and 53 days ahead), searching later dates when an SICC venue has a schedule or maintenance conflict. It refuses inactive/duplicate venue records and does not overwrite capacities of existing configured venues.

**Capacity disclosure:** The uploaded SICC directory lists spaces but does **not** give verified capacities. When a space is not yet in Venue Management, the seed script creates a venue record with an **explicitly marked fictional academic-test capacity** so the application can demonstrate ticket allocations and booking. These values are *not actual approved SICC capacities*. They must be verified and replaced before any real event use. The public event-details page displays this warning for those demo-created venues.

No official SICC endorsement or real customer transaction is claimed. All payment operations remain simulated.

## Recommended manual checks

1. Confirm the five fictional events are added to the existing Technology Showcase (normally **six events total**, unless you have other published events).
2. Confirm the cards show SICC venue, Level, ticket category names, price range, Details and Book now.
3. Open Details and inspect session dates, category prices, and remaining inventory; use **Book now** to open the pre-existing checkout page.
4. Open Bookings, Tickets, Rewards, Support and Profile. Check the sidebar remains visible on desktop, while narrow screens use the Customer menu toggle.
5. If you have no booking history, confirm the page offers **Browse events** instead of an empty table.
6. Run the database smoke test, then simulate one booking/payment in XAMPP. The tests bundled here are database-free render/fixture tests; full MySQL integration cannot be verified in this build environment.

## Rollback

Restore your backed-up PHP/CSS files. The seeder does not run automatically. If already executed, it has added fictional demo events and potentially marked demo-only venues to your MySQL database; remove them with authorised administrative controls only after checking dependencies and audit records. Never truncate shared customer/booking tables to undo a demo seed.
