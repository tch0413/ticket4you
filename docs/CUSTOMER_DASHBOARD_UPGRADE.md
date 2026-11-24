# Tickets4You — Customer Dashboard & Sidebar Upgrade

This update is for the current project located at `C:\xampp\htdocs\T4Y` and its local URL `http://localhost/T4Y/index.php`.

## Changes

- After a successful **Customer** portal sign-in, the existing `?page=dashboard` route opens an **event-discovery-first customer dashboard** instead of leading with empty booking and rewards tables.
- A persistent, responsive **customer sidebar** provides links to Overview, Explore Events, My Bookings, My Tickets, Saved Events, Rewards, Recommendations, Notifications, Support/Refunds, Event Applications, My Profile, Security, and the SICC Floor Directory.
- Events appear as cards for published SICC events with upcoming scheduled sessions. Customers can search by title, description, category, or venue and filter by event category.
- The dashboard shows quick counts for upcoming paid bookings, currently valid tickets, and saved events.
- New read-only customer pages present customer-scoped order history, owned e-tickets, saved upcoming events, rewards points, and existing recommendations.
- Event cards still use the existing Booking Management flow to reserve tickets; no new checkout/payment logic is introduced.
- Favourite toggling on the new customer cards uses its own CSRF-protected action, validates the event, and returns to the current customer page.
- Staff, Organiser, and Admin dashboards and their separate login pages remain unchanged.
- No database migration is required; all existing bookings/tickets/accounts remain intact.

## Install the small upgrade ZIP (recommended)

1. Back up `C:\xampp\htdocs\T4Y` and your database. Keep your existing `storage` folder, app key, and configuration.
2. Open `Tickets4You_Customer_Dashboard_Upgrade.zip` and **copy its files and folders into `C:\xampp\htdocs\T4Y`**. The ZIP paths start with `app`, `assets`, `shared`, and `index.php`, *not* with another nested `T4Y` folder.
3. Select **Replace existing files** when asked, after checking your backup. This upgrade does not include `config.php`, `storage/`, `schema.sql` or `app/core.php`, so it will not reset your local database credentials or secret key.
4. Start Apache and MySQL in XAMPP. Refresh with Ctrl+F5 and sign in using the **Customer** portal.
5. The URL after customer login should be `http://localhost/T4Y/index.php?page=dashboard` and **Upcoming SICC Events** should be shown above event cards.

You do not need to rerun `scripts/install.php` or `scripts/demo_seed.php`.

## Verify

In a terminal:

```powershell
cd C:\xampp\htdocs\T4Y
C:\xampp\php\php.exe tests\customer_portal_smoke.php
C:\xampp\php\php.exe tests\mysql_smoke.php
```

A few visual checks:

- Customer login shows the event-focused dashboard and sidebar. Verify your previously seeded fictional event appears if still upcoming and published.
- Search and Category filters narrow the events; Save event adds/removes a favourite.
- My Bookings and My Tickets show **only this customer's** records. A formal transfer changes the ticket holder; transferred tickets are excluded from the original holder's customer ticket list.
- Explore an event, select ticket quantity, complete simulated checkout, and return to My Tickets to access the QR through the original Booking screen.
- Sidebar collapses into a **Customer menu** button on narrow screens.
- Sign out, then sign in with Admin or Staff to confirm their existing portal/dashboard remains unchanged.

## Current limitations

- The displayed event price is the minimum configured active non-complimentary category price; it is not a promise of live ticket availability. Inventory is rechecked when using the booking workflow.
- The displayed event card artwork is generated with CSS design shapes, not official SICC event photography.
- The customer *My Tickets* list limits display to tickets whose current `holder_email` matches the logged-in customer's email. Some legacy accounts that changed email without updating ticket holder records may need reconciliation.
- The existing payment, QR, refund, and notification workflows are still prototype features requiring local end-to-end testing and security review before any real deployment.
- The shared application still uses some English labels in older module pages; new sidebar and major customer navigation labels use the existing multilingual dictionary.

## Unchanged

The database name is whichever name your working `config.php`/environment or `app/core.php` already uses. If you have moved the project to `C:\xampp\htdocs\T4Y`, verify `T4Y_BASE_URL` (if set) points to `http://localhost/T4Y/index.php`, particularly for email verification or password recovery links.
