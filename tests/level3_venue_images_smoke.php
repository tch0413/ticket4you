<?php
declare(strict_types=1);
// Database-free integration check for the ten Level 3 image assets.
require_once dirname(__DIR__) . '/shared/FloorDirectory.php';
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function tr(string $v): string { return $v; }
function isAdmin(): bool { return false; }
function checkL3(bool $ok, string $name): void {
    if (!$ok) { fwrite(STDERR, "FAIL $name\n"); exit(1); }
    echo "PASS $name\n";
}
$images = [
    'Jesselton Square (Open Plaza)' => 'jesselton-square.webp',
    'Danum (VVIP Room)' => 'danum.webp',
    'Imbak I (VVIP Room)' => 'imbak-i.webp',
    'Imbak Lounge (VIP Lounge)' => 'imbak-lounge.webp',
    'Imbak II (VVIP Room)' => 'imbak-ii.webp',
    'VIP/Imbak Foyer' => 'vip-imbak-foyer.webp',
    'Lobby' => 'lobby.webp',
    'Meranti Cafe' => 'meranti-cafe.webp',
    'Porte Cochere' => 'porte-cochere.webp',
    'Sayat-Sayat' => 'sayat-sayat.webp',
];
foreach ($images as $name => $file) {
    $path = 'assets/venues/' . $file;
    checkL3(siccVenueImage($name, 3) === $path, "Correct image mapping: $name");
    checkL3(is_file(dirname(__DIR__) . '/' . $path), "Image exists: $file");
    checkL3(siccVenueRecord($name, 3)['image'] === $path, "Modal image correct: $name");
}
checkL3(siccVenueImage('Jesselton Square (Open Plaza)', 2) === null, 'Level 3 image not assigned to Level 2');
checkL3(siccVenueImage('Kinabatangan Foyer', 2) !== null, 'Existing Level 2 image preserved');
ob_start(); floorDirectoryView(); $html = (string) ob_get_clean();
checkL3(substr_count($html, 'class="room-tile-photo"') === 25, '25 venue pictures now render across Levels 2,3 and 4');
checkL3(substr_count($html, 'data-room-name=') === 40, 'All 40 rooms remain in directory');
checkL3(str_contains($html, 'modal-dialog-scrollable'), 'Existing same-page scrollable modal retained');
