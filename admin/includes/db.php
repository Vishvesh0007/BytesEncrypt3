<?php
/**
 * Database bootstrap. Uses SQLite so the site works without a separate
 * MySQL server — swap the DSN below for MySQL/PDO if you prefer.
 *
 * This version fails LOUDLY with a plain-English message instead of a
 * blank 500 error, so a hosting issue (missing extension, unwritable
 * folder) is obvious instead of showing a generic "This page isn't working".
 */

function be_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    if (!extension_loaded('pdo_sqlite')) {
        be_fatal_setup_error(
            "The pdo_sqlite PHP extension is not enabled on this server.\n" .
            "Ask your host to enable it (cPanel: 'Select PHP Version' → Extensions → check pdo_sqlite)."
        );
    }

    $dataDir = __DIR__ . '/../../data';
    if (!is_dir($dataDir)) {
        be_fatal_setup_error("The data/ folder is missing. It should sit next to admin/ and blog.php at your site root.");
    }
    if (!is_writable($dataDir)) {
        be_fatal_setup_error(
            "The data/ folder exists but isn't writable by PHP.\n" .
            "Fix: set its permissions to 755 (or 775 if your host requires group-write access)."
        );
    }

    $dbPath = $dataDir . '/blog.sqlite';
    $isNew  = !file_exists($dbPath);

    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } catch (PDOException $e) {
        be_fatal_setup_error("Could not open the database file: " . $e->getMessage());
    }

    if ($isNew) {
        be_create_schema($pdo);
    }

    return $pdo;
}

/**
 * Show a plain, readable error instead of letting an uncaught exception
 * produce a blank 500. Only ever fires on a genuine environment problem.
 */
function be_fatal_setup_error(string $message): void
{
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Setup problem — the site can't reach its database yet.\n\n";
    echo $message . "\n\n";
    echo "Once fixed, reload this page. (Upload diagnose.php to your site root for a full environment check.)\n";
    exit;
}

function be_create_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT UNIQUE NOT NULL,
            excerpt TEXT,
            content TEXT NOT NULL,
            featured_image TEXT,
            youtube_url TEXT,
            category TEXT,
            meta_title TEXT,
            meta_description TEXT,
            status TEXT NOT NULL DEFAULT 'draft',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL,
            attempted_at INTEGER NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT,
            company TEXT,
            service_interest TEXT,
            message TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'new',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contact_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL,
            attempted_at INTEGER NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT UNIQUE NOT NULL,
            department TEXT,
            location TEXT,
            employment_type TEXT,
            summary TEXT,
            description TEXT NOT NULL,
            how_to_apply TEXT,
            status TEXT NOT NULL DEFAULT 'draft',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts(ip, attempted_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_contact_attempts_ip ON contact_attempts(ip, attempted_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status, created_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_leads_status ON leads(status, created_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_jobs_status ON jobs(status, created_at)");
}
