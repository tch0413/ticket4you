<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once dirname(__DIR__) . '/app/core.php';
try {
 $expected=cfg('T4Y_DB_NAME','t4y_chatgpt_db');
 $actual=(string)db()->query('SELECT DATABASE()')->fetchColumn();
 if($actual!==$expected)throw new RuntimeException('Database mismatch; check your environment and backup.');
 db()->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS account_password_otps (
 user_id BIGINT UNSIGNED PRIMARY KEY,
 otp_hash CHAR(64) NOT NULL,
 attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
 sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 expires_at DATETIME NOT NULL,
 used_at DATETIME NULL,
 FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB
SQL);
 db()->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS customer_research_profiles (
 user_id BIGINT UNSIGNED PRIMARY KEY,
 age_band VARCHAR(16) NULL, gender VARCHAR(32) NULL, region VARCHAR(64) NULL,
 occupation VARCHAR(64) NULL, interests_json TEXT NULL,
 analytics_consent TINYINT(1) NOT NULL DEFAULT 0,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB
SQL);
 echo "PASS UAM Phase 2 tables created or already present in $actual. Existing records unchanged.\n";
} catch(Throwable $e){fwrite(STDERR,'FAIL migration: '.$e->getMessage()."\n");exit(1);}
