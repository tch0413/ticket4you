<?php
declare(strict_types=1);
/** Persistent, per-portal and per-identifier sign-in throttling. Never store login IDs in this table. */
const T4Y_LOGIN_WINDOW_SECONDS = 900;
const T4Y_LOGIN_MAX_FAILURES = 5;

function loginThrottleKey(string $login, string $portal): string {
    // Login identifiers were already normalized by loginWithPassword().
    return hash_hmac('sha256', $portal . "\0" . $login, secret());
}

function loginThrottleCheck(string $login, string $portal): void {
    $row = one('SELECT blocked_until FROM auth_login_attempts WHERE subject_key=?', [loginThrottleKey($login, $portal)]);
    if ($row && !empty($row['blocked_until']) && strtotime((string)$row['blocked_until']) > time()) {
        throw new DomainException('Too many sign-in attempts. Please try again in 15 minutes.');
    }
}

function loginThrottleFailure(string $login, string $portal): void {
    $key = loginThrottleKey($login, $portal);
    // Establish the row before locking; concurrent first failures cannot race to INSERT.
    q('INSERT IGNORE INTO auth_login_attempts(subject_key,failures,window_started_at) VALUES(?,0,NOW())', [$key]);
    tx(static function () use ($key): void {
        $row = one('SELECT failures,window_started_at,blocked_until FROM auth_login_attempts WHERE subject_key=? FOR UPDATE', [$key]);
        if (!$row) throw new RuntimeException('Login throttle record unavailable.');
        $now = time();
        $start = strtotime((string)$row['window_started_at']);
        $priorBlock = !empty($row['blocked_until']) ? strtotime((string)$row['blocked_until']) : false;
        $reset = $start === false || ($now - $start) >= T4Y_LOGIN_WINDOW_SECONDS
            || ($priorBlock !== false && $priorBlock <= $now);
        $failures = $reset ? 1 : (int)$row['failures'] + 1;
        $blockedUntil = $failures >= T4Y_LOGIN_MAX_FAILURES
            ? date('Y-m-d H:i:s', $now + T4Y_LOGIN_WINDOW_SECONDS) : null;
        q('UPDATE auth_login_attempts SET failures=?,window_started_at=?,blocked_until=? WHERE subject_key=?', [
            $failures, $reset ? date('Y-m-d H:i:s', $now) : $row['window_started_at'], $blockedUntil, $key
        ]);
    });
}

function loginThrottleSuccess(string $login, string $portal): void {
    q('DELETE FROM auth_login_attempts WHERE subject_key=?', [loginThrottleKey($login, $portal)]);
}
