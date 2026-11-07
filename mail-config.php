<?php
/**
 * Hostinger SMTP settings for the contact form.
 *
 * 1. In hPanel, go to Emails → Mailboxes and create (or use) a mailbox,
 *    e.g. contact@bytesencrypt.com — note its password (this is the mailbox
 *    password, NOT your hPanel/hosting login password).
 * 2. Fill in BE_SMTP_USERNAME and BE_SMTP_PASSWORD below with that mailbox's
 *    address and password.
 * 3. Leave the host/port as-is — smtp.hostinger.com on port 465 (SSL) is
 *    Hostinger's standard setting and works for every plan.
 */

define('BE_SMTP_HOST', 'smtp.gmail.com');
define('BE_SMTP_PORT', 587);
define('BE_SMTP_SECURE', 'ssl'); // 'ssl' for port 465, 'tls' for port 587

// --- Fill these in with your real Hostinger mailbox ---
define('BE_SMTP_USERNAME', 'bytesencrypt@gmail.com');   // the full mailbox address
define('BE_SMTP_PASSWORD', 'gaalpbilexjpvopv'); // its password, from hPanel

define('BE_SMTP_FROM_EMAIL', 'bytesencrypt@gmail.com'); // should match BE_SMTP_USERNAME
define('BE_SMTP_FROM_NAME', 'Bytes Encrypt Website');
define('BE_SMTP_TO_EMAIL', 'bytesencrypt@gmail.com');   // where consultation requests are delivered
