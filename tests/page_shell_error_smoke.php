<?php
/** Regression: a guest error must not nest a second HTML document. */
declare(strict_types=1);

$_GET = ['page' => 'events'];
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
require dirname(__DIR__) . '/index.php';
$html = (string) ob_get_clean();

$checks = [
    'Only one HTML document' => substr_count($html, '<!doctype html>') === 1,
    'Only one site header' => substr_count($html, '<header class="site-header">') === 1,
    'Only one main content wrapper' => substr_count($html, '<main class="main-content"') === 1,
    'Only one site footer' => substr_count($html, '<footer class="site-footer">') === 1,
    'Login protection retained' => str_contains($html, 'Please log in first.'),
    'Partial Event Management view removed' => !str_contains($html, '<h1>Event Management</h1>'),
    'Unauthenticated view uses error status' => http_response_code() === 422,
];

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}
