<?php
declare(strict_types=1);
/**
 * One-time, non-destructive Level 3 image mapping update.
 * Run from project root: php scripts/apply_level3_venue_images.php
 * Keeps existing Level 2 mapping and other application code untouched.
 */
if (PHP_SAPI !== 'cli') { exit("CLI only.\n"); }
$root = dirname(__DIR__);
$target = $root . '/shared/FloorDirectory.php';
if (!is_file($target)) { fwrite(STDERR, "Cannot find shared/FloorDirectory.php.\n"); exit(1); }
$source = (string) file_get_contents($target);
$signature = 'function siccVenueImage(string $name, int $level): ?string {';
if (substr_count($source, $signature) !== 1) {
    fwrite(STDERR, "Unexpected FloorDirectory.php function structure. Nothing changed.\n"); exit(1);
}
$marker = "'Jesselton Square (Open Plaza)' => 'jesselton-square.webp'";
if (str_contains($source, $marker)) {
    echo "Level 3 image mappings already present; PHP mapping not changed.\n";
} else {
    $newBlock = <<<'BLOCK'

    // Level 3 SICC venue illustrations. Level 2 mappings below remain unchanged.
    if ($level === 3) {
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
        return isset($images[$name]) ? 'assets/venues/' . $images[$name] : null;
    }
BLOCK;
    $updated = str_replace($signature, $signature . $newBlock, $source, $replacements);
    if ($replacements !== 1 || $updated === $source) {
        fwrite(STDERR, "Could not safely insert Level 3 mapping. Nothing changed.\n"); exit(1);
    }
    if (file_put_contents($target, $updated, LOCK_EX) === false) {
        fwrite(STDERR, "Cannot write FloorDirectory.php.\n"); exit(1);
    }
    echo "Updated shared/FloorDirectory.php (Level 3 mappings added).\n";
}

// Keep pre-existing tests current without overwriting other edits to those files.
$changes = [
    'tests/venue_gallery_smoke.php' => [
        "substr_count(\$html,'class=\"room-tile-photo\"')===12" => "substr_count(\$html,'class=\"room-tile-photo\"')===22",
        "substr_count(\$html,'loading=\"lazy\"')===12" => "substr_count(\$html,'loading=\"lazy\"')===22",
        'Visitor directory renders twelve venue images' => 'Visitor directory renders twenty-two venue images',
        'All twelve venue pictures use lazy loading' => 'All twenty-two venue pictures use lazy loading',
    ],
    'tests/venue_modal_smoke.php' => [
        "substr_count(\$html,'class=\"room-tile-photo\"')===12" => "substr_count(\$html,'class=\"room-tile-photo\"')===22",
        'All twelve Level 2 venue images are mapped to cards' => 'All twenty-two Level 2 and Level 3 images are mapped to cards',
    ],
];
foreach ($changes as $rel => $rules) {
    $path = $root . '/' . $rel;
    if (!is_file($path)) { echo "Skipped missing optional test: $rel\n"; continue; }
    $old = (string) file_get_contents($path);
    $new = str_replace(array_keys($rules), array_values($rules), $old);
    if ($new !== $old) {
        if (file_put_contents($path, $new, LOCK_EX) === false) {
            fwrite(STDERR, "Could not update $rel\n"); exit(1);
        }
        echo "Updated expectations in $rel.\n";
    } else { echo "No outdated image-count expectations in $rel.\n"; }
}
echo "Done. No database or other module files were changed.\n";
