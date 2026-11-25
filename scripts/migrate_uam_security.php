<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once dirname(__DIR__) . '/app/core.php';

try {
    db()->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS auth_login_attempts (
    subject_key CHAR(64) PRIMARY KEY,
    failures INT UNSIGNED NOT NULL DEFAULT 0,
    window_started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    blocked_until DATETIME NULL,
    INDEX (blocked_until)
) ENGINE=InnoDB
SQL);
    echo "PASS UAM login throttling table is ready. Existing accounts and bookings were not changed.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL UAM migration: ' . $e->getMessage() . "\n");
    exit(1);
}
