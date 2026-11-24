# Tickets4You: SICC Venue Details Modal & Provisional Capacities

## Upgrade scope

The public SICC Floor Directory continues to include all 40 spaces from the supplied floor inventory. Each room tile is now a button opening an accessible, scrollable Bootstrap dialog on the same page. The dialog contains the venue's assigned image (where already available), room description, planning capacity range, level and suggested uses. Closing returns to the directory with search and floor filters intact. No new page or image tab is opened.

Only ten Level 2 venues have previously generated illustrations. Other spaces show a neutral SICC placeholder until their own image is created. These illustrations are **not** confirmed photographs of the existing rooms.

### Indicative capacity table (planning only)

These are broad, nonofficial estimates created for the coursework interface. They **do not** measure safe room occupancy, seating layout capacity or approved ticket inventory. No `venues.capacity` or `event_sessions.capacity` database values are changed. Consult SICC's official capacity chart and obtain venue-manager approval before any real booking or public-capacity claim. A combined room is not additional capacity on top of the component rooms.

| Level | Space | Suggested guest range |
|---|---|---|
| 2 | Kinabatangan Foyer | 350–600 |
| 2 | Kinabatangan I | 700–1,200 |
| 2 | Kinabatangan II | 700–1,200 |
| 2 | Kinabatangan III | 700–1,200 |
| 2 | Business Centre | 20–40 |
| 2 | Kapalai & Manukan | 60–100 |
| 2 | Mabul | 20–40 |
| 2 | Mataking | 30–60 |
| 2 | Balambangan | 40–80 |
| 2 | Silam Lounge (VVIP Lounge) | 15–30 |
| 2 | Silam Foyer | 40–80 |
| 2 | Promenade | Not estimated (outdoor) |
| 3 | Jesselton Square (Open Plaza) | 500–1,500 |
| 3 | Danum (VVIP Room) | 12–24 |
| 3 | Imbak I (VVIP Room) | 15–30 |
| 3 | Imbak Lounge (VIP Lounge) | 20–40 |
| 3 | Imbak II (VVIP Room) | 15–30 |
| 3 | VIP/Imbak Foyer | 40–70 |
| 3 | Lobby | 150–300 |
| 3 | Meranti Cafe | 60–120 |
| 3 | Porte Cochere | 30–60 |
| 3 | Sayat-Sayat | 40–80 |
| 4 | Sipadan Foyer | 300–500 |
| 4 | Sipadan I-III | 2,000–4,000 |
| 4 | PAH Foyer | 200–400 |
| 5 | Kadamaian | 80–150 |
| 5 | Mesilau | 60–120 |
| 5 | Dinawan | 60–120 |
| 5 | Madai | 30–70 |
| 5 | Sepilok | 30–70 |
| 5 | Selingan | 25–50 |
| 5 | Monsopiad | 80–150 |
| 5 | Klias | 30–70 |
| 5 | Mantanani | 40–80 |
| 5 | Rafflesia | 80–160 |
| 5 | Kianbalu I | 80–140 |
| 5 | Kinabalu II | 80–140 |
| 5 | Kinabalu III | 80–140 |
| 5 | Kinabalu I-III | 250–450 |
| 5 | Kinabalu Foyer I | 100–200 |

## Installation on Windows XAMPP

1. Back up `C:\xampp\htdocs\T4Y`, including `storage/app.key`, and back up the current MySQL database.
2. Extract the **Upgrade ZIP** directly into `C:\xampp\htdocs\T4Y` (not into `T4Y\T4Y`). Overwrite matching source files.
3. Start Apache and MySQL. Hard-refresh `http://localhost/T4Y/index.php?page=floor` with Ctrl+F5.
4. Click any venue card; scroll the modal to read the details, then choose **Back to floor directory**, the top Close button, or Escape.
5. Run `C:\xampp\php\php.exe tests\venue_modal_smoke.php` to validate the interface.

This upgrade does not run SQL migrations, modify session capacities, or touch customer records.

## Files modified

- `shared/FloorDirectory.php` — in-page modal markup, 39 provisional ranges, details for all 40 venues.
- `assets/style.css` — card imagery, modal layout, desktop and mobile scrolling.
- `assets/app.js` — safely populate modal from server-provided venue metadata.
- `tests/venue_gallery_smoke.php`, `tests/venue_modal_smoke.php` — regression tests.
- `assets/venues/` — existing ten venue images included for portability.
