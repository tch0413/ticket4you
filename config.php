<?php
declare(strict_types=1);
// Override through environment variables on XAMPP. No production credentials in source.
const APP_NAME = 'Tickets4You';
const APP_TZ = 'Asia/Kuching';
date_default_timezone_set(APP_TZ);
function cfg(string $key, string $fallback = ''): string { $value = getenv($key); return $value === false ? $fallback : $value; }
