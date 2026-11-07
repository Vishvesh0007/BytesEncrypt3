<?php
/**
 * Security helpers: CSRF protection, a self-contained math CAPTCHA
 * (no external service or GD dependency), and IP-based login rate limiting.
 */

require_once __DIR__ . '/db.php';

function be_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

/* ---------------- CSRF ---------------- */

function be_csrf_token(): string
{
    be_start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function be_csrf_field(): string
{
    $token = htmlspecialchars(be_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function be_csrf_verify(): bool
{
    be_start_session();
    $sent = $_POST['csrf_token'] ?? '';
    $real = $_SESSION['csrf_token'] ?? '';
    return $sent !== '' && $real !== '' && hash_equals($real, $sent);
}

/* ---------------- CAPTCHA (simple math, no external dependency) ---------------- */

function be_captcha_generate(): array
{
    be_start_session();
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $_SESSION['captcha_answer'] = $a + $b;
    return [$a, $b];
}

function be_captcha_verify(string $answer): bool
{
    be_start_session();
    $expected = $_SESSION['captcha_answer'] ?? null;
    unset($_SESSION['captcha_answer']); // one-time use
    if ($expected === null || $answer === '') {
        return false;
    }
    return ((int) $answer) === (int) $expected;
}

/* ---------------- Rate limiting ---------------- */

const BE_MAX_ATTEMPTS   = 5;     // failed attempts allowed
const BE_LOCKOUT_SECONDS = 900;  // 15 minutes

function be_client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function be_rate_limited(): bool
{
    $pdo = be_db();
    $ip  = be_client_ip();
    $since = time() - BE_LOCKOUT_SECONDS;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = :ip AND attempted_at > :since');
    $stmt->execute(['ip' => $ip, 'since' => $since]);
    return (int) $stmt->fetchColumn() >= BE_MAX_ATTEMPTS;
}

function be_record_failed_attempt(): void
{
    $pdo = be_db();
    $stmt = $pdo->prepare('INSERT INTO login_attempts (ip, attempted_at) VALUES (:ip, :t)');
    $stmt->execute(['ip' => be_client_ip(), 't' => time()]);
}

function be_clear_failed_attempts(): void
{
    $pdo = be_db();
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip = :ip');
    $stmt->execute(['ip' => be_client_ip()]);
}

function be_seconds_until_unlock(): int
{
    $pdo = be_db();
    $ip  = be_client_ip();
    $stmt = $pdo->prepare('SELECT MAX(attempted_at) FROM login_attempts WHERE ip = :ip');
    $stmt->execute(['ip' => $ip]);
    $last = (int) $stmt->fetchColumn();
    $unlockAt = $last + BE_LOCKOUT_SECONDS;
    return max(0, $unlockAt - time());
}

/* ---------------- Output helpers ---------------- */

function be_e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
