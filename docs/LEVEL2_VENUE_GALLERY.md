# Level 2 venue gallery — Tickets4You

This upgrade adds the ten generated interior images to the correct named SICC Level 2 entries in the public floor directory. Visitors can see each image directly on a room card and click it for a larger full-size version. No visible image badge or label is added.

## Install to XAMPP

1. Back up `C:\xampp\htdocs\T4Y`.
2. Extract the **upgrade ZIP** directly into `C:\xampp\htdocs\T4Y`, replacing matching files. Do **not** create an extra `T4Y` directory.
3. Keep your existing `config.php`, database, `storage/app.key`, and other runtime files unchanged.
4. Open `http://localhost/T4Y/index.php?page=floor` and refresh with Ctrl+F5.
5. Choose **Level 2** and click a venue image to view it at full size.
6. Run `C:\xampp\php\php.exe tests\venue_gallery_smoke.php` to verify image mapping and rendering (no database required).

## Image mapping

| Directory venue | File in `assets/venues/` |
|---|---|
| Kinabatangan Foyer | kinabatangan-foyer.webp |
| Kinabatangan I | kinabatangan-i.webp |
| Kinabatangan II | kinabatangan-ii.webp |
| Kinabatangan III | kinabatangan-iii.webp |
| Business Centre | business-centre.webp |
| Kapalai & Manukan | kapalai-manukan.webp |
| Mabul | mabul.webp |
| Mataking | mataking.webp |
| Balambangan | balambangan.webp |
| Silam Lounge (VVIP Lounge) | silam-lounge.webp |

The two remaining Level 2 entries (Silam Foyer and Promenade) and Level 3–5 entries retain their existing non-photo directory cards until further images are supplied. No database change or official venue-capacity change is made. The images are AI-generated concepts inspired by the SICC website, **not authenticated photographs of the actual rooms**; the public layout intentionally does not add individual on-image labels.
