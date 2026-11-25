<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/shared/FloorDirectory.php';

$images = [
    'Kadamaian' => 'kadamaian.webp',
    'Mesilau' => 'mesilau.webp',
    'Dinawan' => 'dinawan.webp',
    'Madai' => 'madai.webp',
    'Sepilok' => 'sepilok.webp',
    'Selingan' => 'selingan.webp',
    'Monsopiad' => 'monsopiad.webp',
    'Klias' => 'klias.webp',
    'Mantanani' => 'mantanani.webp',
    'Rafflesia' => 'rafflesia.webp',
    'Kianbalu I' => 'kianbalu-i.webp',
    'Kinabalu II' => 'kinabalu-ii.webp',
    'Kinabalu III' => 'kinabalu-iii.webp',
    'Kinabalu I-III' => 'kinabalu-i-iii.webp',
    'Kinabalu Foyer I' => 'kinabalu-foyer-i.webp',
];

foreach ($images as $name => $file) {
    $expected = 'assets/venues/' . $file;
    $actual = siccVenueImage($name, 5);

    if ($actual !== $expected) {
        fwrite(STDERR, "FAIL Image mapping: $name\n");
        exit(1);
    }

    echo "PASS Correct image mapping: $name\n";

    $path = dirname(__DIR__) . '/' . $expected;

    if (!is_file($path) || !getimagesize($path)) {
        fwrite(STDERR, "FAIL Missing or invalid image: $file\n");
        exit(1);
    }

    echo "PASS Image file valid: $file\n";

    if (siccVenueImage($name, 4) !== null) {
        fwrite(STDERR, "FAIL Wrong floor assignment: $name\n");
        exit(1);
    }
}

echo "PASS All 15 Level 5 images correctly mapped.\n";
