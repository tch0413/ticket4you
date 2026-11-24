# Tickets4You — UI refresh & SICC floor directory

## Purpose

This update refines the existing PHP system's visual interface without changing its MySQL schema, bookings, payments or ticket logic.

- Dark navy/champagne navigation and responsive, more readable cards, forms, buttons and tables.
- Compact top navigation with role-aware management dropdowns; small screens get a collapsible menu.
- **One visible language control** displaying the current selection (English, Bahasa Melayu, 简体中文 or 繁體中文); the alternatives are available inside its dropdown.
- Fix for the earlier `localeCode()` undefined `$_SESSION['locale']` warning.
- Upgraded public landing page and event cards.
- Public, searchable and filterable SICC floor-space **directory**.
- Admin venue-management prefill links from any directory entry. Actual capacity must be verified and entered before venue creation.

**Directory is not a geometric floor plan.** Only the room names and level groupings supplied by the project team are used. No area, adjacency, location within a floor, approved capacity, status or availability is fabricated. `Kianbalu I` is preserved exactly as given; verify the official room spelling before external publication.

## Update your existing XAMPP copy

1. **Back up** `C:\xampp\htdocs\T4Y_Chatgpt\T4Y` first. Keep `storage\app.key` safe (it is needed for signed QR tickets and encrypted MFA factors).
2. Download `Tickets4You_UI_Upgrade.zip`. Open it and extract **its contents** directly into `C:\xampp\htdocs\T4Y_Chatgpt\T4Y`, choosing to replace matching source files. Confirm `C:\xampp\htdocs\T4Y_Chatgpt\T4Y\index.php` is still the entrypoint; do not create an extra `T4Y` nested folder.
3. Do **not** delete the existing database or run `scripts/install.php` again. Existing venue records and bookings remain untouched.
4. Start Apache and MySQL. Open **http://localhost/T4Y_Chatgpt/T4Y/index.php** and press `Ctrl + F5` to refresh the stylesheet cache.
5. Open **Floor directory** in the header, choose a floor or search for a room. As Admin, select **Add to venue management** to prefill its name and level; enter a **verified** physical capacity to create the real venue record.
6. In PowerShell:

   ```powershell
   cd C:\xampp\htdocs\T4Y_Chatgpt\T4Y
   C:\xampp\php\php.exe tests\ui_smoke.php
   C:\xampp\php\php.exe tests\mysql_smoke.php
   ```

   `ui_smoke.php` checks language fallback and the 40-space inventory without connecting to MySQL. The existing MySQL smoke test checks your working installation.

## Changed files

- `app/layout.php` — new responsive global navigation, current-language dropdown, shared page headings and component styling.
- `app/i18n.php` — stable locale handling; translations for the new public UI/navigation labels.
- `app/core.php` — default email-link base URL for the nested XAMPP folder and `t4y_chatgpt_db` database selected for this installation.
- `assets/style.css` — new design system.
- `assets/app.js` — directory filtering, keeps existing confirm/QR behavior.
- `index.php` — new `?page=floor` route.
- `modules/Event.php` — refreshed event-discovery page.
- `modules/Venue.php` — admin prefill from directory; existing venue CRUD remains separate.
- `shared/FloorDirectory.php` — new public floor directory with names from the supplied table.
- `tests/ui_smoke.php` — basic standalone validation.

## Limitations

Other module bodies and many notifications are still authored in English. The language selector correctly reflects the active locale and the new navigation/public wording is translated where configured; this update is **not** a fully translated four-language application. Actual room capacities, floor-plan geometry and official space availability remain unknown until supplied and verified. Visual inspection and browser testing on the user's local XAMPP setup are still required.
