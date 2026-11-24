<?php
declare(strict_types=1);
// No database needed. Run with: C:\xampp\php\php.exe tests\venue_gallery_smoke.php
require_once dirname(__DIR__) . '/shared/FloorDirectory.php';
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function tr(string $value): string { return $value; }
function isAdmin(): bool { return false; }
function verifyVenueGallery(bool $ok, string $description): void {
 if (!$ok) { fwrite(STDERR, 'FAIL '.$description."\n"); exit(1); }
 echo 'PASS '.$description."\n";
}
$map = [
 'Kinabatangan Foyer' => 'kinabatangan-foyer.webp',
 'Kinabatangan I' => 'kinabatangan-i.webp',
 'Kinabatangan II' => 'kinabatangan-ii.webp',
 'Kinabatangan III' => 'kinabatangan-iii.webp',
 'Business Centre' => 'business-centre.webp',
 'Kapalai & Manukan' => 'kapalai-manukan.webp',
 'Mabul' => 'mabul.webp',
 'Mataking' => 'mataking.webp',
 'Balambangan' => 'balambangan.webp',
 'Silam Lounge (VVIP Lounge)' => 'silam-lounge.webp',
];
foreach ($map as $name => $file) {
 $uri = siccVenueImage($name, 2);
 verifyVenueGallery($uri==='assets/venues/'.$file, 'Correct image mapped to '.$name);
 verifyVenueGallery(is_file(dirname(__DIR__).'/'.$uri), 'Image file exists: '.$file);
}
verifyVenueGallery(siccVenueImage('Silam Foyer',2)===null, 'No incorrect image assigned to Silam Foyer');
verifyVenueGallery(siccVenueImage('Promenade',2)===null, 'No incorrect image assigned to Promenade');
verifyVenueGallery(siccVenueImage('Kinabatangan I',3)===null, 'Level 2 image never assigned to wrong floor');
ob_start(); floorDirectoryView(); $html=(string)ob_get_clean();
verifyVenueGallery(substr_count($html,'class="room-tile-photo"')===10, 'Visitor directory renders ten venue images');
verifyVenueGallery(substr_count($html,'data-room-name=')===40, 'All forty directory rooms remain visible');
verifyVenueGallery(substr_count($html,'loading="lazy"')===10, 'Venue pictures use lazy loading');
verifyVenueGallery(!str_contains($html,'AI-generated'), 'No visible image labels added');
