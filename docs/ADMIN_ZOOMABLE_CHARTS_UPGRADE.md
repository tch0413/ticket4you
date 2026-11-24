# Tickets4You — Zoomable Admin Analytics

This update applies to the PHP 8 / XAMPP Tickets4You project at `C:\xampp\htdocs\T4Y`.

## Install

1. Make a backup of `C:\xampp\htdocs\T4Y` and export your MySQL database.
2. Open `Tickets4You_Admin_Zoomable_Charts_Upgrade.zip` and copy its contents directly into `C:\xampp\htdocs\T4Y`; replace matching files.
3. Keep `config.php`, `storage/app.key`, your MySQL database, users and bookings. This patch has **no SQL changes** and must not run the installer.
4. Refresh the browser with `Ctrl + F5` (CSS and JavaScript files are cache-busted).
5. Sign in through the **Administrator** portal. Open Dashboard, then click any **time-series chart** for its detailed view.

## Zoom and navigation

- **Scroll the mouse wheel over the detailed chart** to zoom in or out around the pointer position. Double-click to zoom in.
- Use **Zoom in / Zoom out** buttons for accessible controls.
- **Drag left/right** across the graph with a mouse or pen to pan through historical data. On touch devices, the page can still be scrolled normally; use the zoom buttons.
- **Reset view** restores the selected time range. **10-year view** zooms all the way out.
- Keyboard controls while the chart has focus: `+`, `-`, `Left Arrow`, `Right Arrow`.
- Use the **Time range** dropdown for 7, 30, 90 days, 12 months, 5 years, 10 years or custom dates. The event filter continues to work.
- Detailed table and total are recomputed from **recorded** payments, tickets, refunds and admissions when you zoom or pan.
- Graph granularity adapts automatically: up to 3 days = hourly; up to 95 days = daily; up to 1900 days = monthly; longer periods = yearly.
- The custom time window is limited to a 10-year horizon ending today (SICC's `Asia/Kuching` timezone). No fake figures are added.
- Zooming works on the five **time-series** graphs: Gross revenue, Paid bookings, Paid tickets issued, Completed refunds and First admissions. Three comparison/bar charts remain clickable for detailed breakdown views, but do not have time-axis zoom (no timeline on those charts).
- Chart-data refresh requests use the user's current authentication and server-side Admin checks and do not extend login idle time.
- The full current zoom window is retained in the URL, so you may copy it or change interface language without losing the time range.

## Verify in your own XAMPP

Run in PowerShell:

```powershell
cd C:\xampp\htdocs\T4Y
C:\xampp\php\php.exe tests\admin_analytics_smoke.php
C:\xampp\php\php.exe tests\analytics_zoom_smoke.php
C:\xampp\php\php.exe tests\mysql_smoke.php
```

Then view `http://localhost/T4Y/index.php?page=dashboard` as Admin. Click the Gross revenue graph, zoom out to 10 years, then zoom into 1 day and confirm the detailed table shows 24 hourly buckets. For zero bookings, the chart remains at zero without invented values.

This environment cannot verify live MySQL-backed zoom requests against your Windows installation. The tests verify date bucketing, API routing, SVG generation, translations, and static UI controls; end-to-end browser/MySQL confirmation must be done locally.
