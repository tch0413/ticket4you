# Tickets4You – Level 3 venue image upgrade

This package adds ten **illustrative** WebP venue images to the SICC Floor Directory. These are original concept visuals; they are not verified photographs of the physical SICC rooms. No text labels are added to the images by the web app. Existing capacity metadata, ticketing, other modules and database contents are not modified.

## Install safely with Git (Windows PowerShell)

1. Make sure your team has merged any previous Level 2 changes into `main`, and preserve existing uncommitted edits.
2. `cd C:\xampp\htdocs\T4Y`
3. `git switch main`
4. `git pull --ff-only origin main`
5. `git switch -c feature/level3-venue-images`
6. Extract **the contents** of this ZIP directly into `C:\xampp\htdocs\T4Y`. The ZIP contains only new image files, one new test, and a one-time patch script. It does **not** include or replace `shared/FloorDirectory.php`.
7. Run `C:\xampp\php\php.exe scripts\apply_level3_venue_images.php`.
8. Run these checks:
   - `C:\xampp\php\php.exe -l shared\FloorDirectory.php`
   - `C:\xampp\php\php.exe tests\level3_venue_images_smoke.php`
   - `C:\xampp\php\php.exe tests\venue_gallery_smoke.php`
   - `C:\xampp\php\php.exe tests\venue_modal_smoke.php`
9. Browse `http://localhost/T4Y/index.php?page=floor`, select Level 3, then click a room to open its existing on-page detail modal.
10. Use `git status` and `git diff` to review changes before staging/committing; publish via a feature branch and Pull Request.

## Exact image-to-room assignments

| Directory venue | Image file |
| --- | --- |
| Jesselton Square (Open Plaza) | `jesselton-square.webp` |
| Danum (VVIP Room) | `danum.webp` |
| Imbak I (VVIP Room) | `imbak-i.webp` |
| Imbak Lounge (VIP Lounge) | `imbak-lounge.webp` |
| Imbak II (VVIP Room) | `imbak-ii.webp` |
| VIP/Imbak Foyer | `vip-imbak-foyer.webp` |
| Lobby | `lobby.webp` |
| Meranti Cafe | `meranti-cafe.webp` |
| Porte Cochere | `porte-cochere.webp` |
| Sayat-Sayat | `sayat-sayat.webp` |

Do not run `scripts/install.php`. The helper is idempotent: re-running it leaves Level 3 mappings intact.
