<?php
declare(strict_types=1);
const APP_NAME = 'EventSphere';
const APP_TZ = 'Asia/Kuching';
date_default_timezone_set(APP_TZ);
// Environment variables take precedence; the ignored local file works in both Apache and CLI.
function cfg(string $key, string $fallback = ''): string {
    static $local = null;
    if ($local === null) {
        $path = __DIR__.'/config/database.local.php';
        $local = is_file($path) ? require $path : [];
        if (!is_array($local)) throw new RuntimeException('Invalid local configuration.');
    }
    $value = getenv($key);
    if ($value !== false) return $value;
    return array_key_exists($key,$local) ? (string)$local[$key] : $fallback;
}
