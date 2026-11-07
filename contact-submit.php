<?php
/**
 * Handles the public "Book a consultation" form on contact.php.
 * No login required — this is a public lead-capture endpoint, so it relies
 * on a honeypot field + per-IP rate limiting instead of a session CSRF token.
 */
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/mail-config.php';
require_once __DIR__ . '/libs/PHPMailer/Exception.php';
require_once __DIR__ . '/libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function be_contact_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

const BE_CONTACT_MAX_PER_HOUR = 5;

function be_contact_rate_limited(PDO $pdo): bool
{
    $since = time() - 3600;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM contact_attempts WHERE ip = :ip AND attempted_at > :since');
    $stmt->execute(['ip' => be_contact_ip(), 'since' => $since]);
    return (int) $stmt->fetchColumn() >= BE_CONTACT_MAX_PER_HOUR;
}

function be_contact_record_attempt(PDO $pdo): void
{
    $stmt = $pdo->prepare('INSERT INTO contact_attempts (ip, attempted_at) VALUES (:ip, :t)');
    $stmt->execute(['ip' => be_contact_ip(), 't' => time()]);
}

function be_redirect(string $qs): void
{
    header('Location: contact.php' . $qs);
    exit;
}

/** Truncate a string to a max length without requiring the mbstring extension. */
function be_truncate(string $s, int $maxLen): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($s, 0, $maxLen);
    }
    return substr($s, 0, $maxLen);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    be_redirect('');
}

$pdo = be_db();

// Honeypot: real visitors never fill this hidden field in.
if (!empty($_POST['website'])) {
    be_redirect('?error=spam');
}

if (be_contact_rate_limited($pdo)) {
    be_redirect('?error=rate');
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$company = trim($_POST['company'] ?? '');
$service = trim($_POST['service_interest'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    be_contact_record_attempt($pdo);
    be_redirect('?error=missing');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    be_contact_record_attempt($pdo);
    be_redirect('?error=invalid_email');
}

// Trim to reasonable lengths regardless of client-side maxlength.
$name    = be_truncate($name, 120);
$email   = be_truncate($email, 160);
$phone   = be_truncate($phone, 40);
$company = be_truncate($company, 160);
$service = be_truncate($service, 120);
$message = be_truncate($message, 4000);

be_contact_record_attempt($pdo);

$stmt = $pdo->prepare('
    INSERT INTO leads (name, email, phone, company, service_interest, message, status, created_at)
    VALUES (:name, :email, :phone, :company, :service, :message, \'new\', :created_at)
');
$stmt->execute([
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'company' => $company,
    'service' => $service,
    'message' => $message,
    'created_at' => date('Y-m-d H:i:s'),
]);

// Email notification via Hostinger SMTP (see mail-config.php for credentials).
// The lead above is already saved to the database regardless of whether this
// succeeds, so a misconfigured mailbox never loses a submission — it'll just
// be visible in admin/leads.php instead of your inbox until it's fixed.
$subject = 'New consultation request from ' . $name;
$bodyLines = [
    "New consultation request via the website:",
    "",
    "Name: {$name}",
    "Email: {$email}",
    "Phone: " . ($phone !== '' ? $phone : '—'),
    "Company: " . ($company !== '' ? $company : '—'),
    "Interested in: " . ($service !== '' ? $service : '—'),
    "",
    "Message:",
    $message,
];
$body = implode("\n", $bodyLines);

try {
    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host       = BE_SMTP_HOST;
    $mailer->Port       = BE_SMTP_PORT;
    $mailer->SMTPSecure = BE_SMTP_SECURE === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mailer->SMTPAuth   = true;
    $mailer->Username   = BE_SMTP_USERNAME;
    $mailer->Password   = BE_SMTP_PASSWORD;
    $mailer->CharSet    = 'UTF-8';

    $mailer->setFrom(BE_SMTP_FROM_EMAIL, BE_SMTP_FROM_NAME);
    $mailer->addAddress(BE_SMTP_TO_EMAIL);
    $mailer->addReplyTo($email, $name);

    $mailer->isHTML(false);
    $mailer->Subject = $subject;
    $mailer->Body    = $body;

    $mailer->send();
} catch (PHPMailerException $e) {
    // Swallow the error — the lead is safely stored in the database either way.
    // If email delivery keeps failing, check admin/leads.php and verify the
    // credentials in mail-config.php against hPanel > Emails > Mailboxes.
}

be_redirect('?sent=1');
