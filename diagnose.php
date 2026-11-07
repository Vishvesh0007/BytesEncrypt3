<?php
/**
 * TEMPORARY diagnostic script. Upload this to your site root, visit it in
 * a browser, read the results, then DELETE IT — it reveals server details
 * that shouldn't stay live.
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== Bytes Encrypt — Server Diagnostic ===\n\n";

echo "PHP version: " . PHP_VERSION . "\n";
$phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
echo $phpOk ? "  OK (7.4+ required)\n" : "  ✗ PROBLEM — you need PHP 7.4 or higher. Change this in your hosting control panel's 'MultiPHP' or 'PHP Selector' tool.\n";

echo "\n--- Required extensions ---\n";
$required = ['pdo', 'pdo_sqlite', 'sqlite3', 'fileinfo', 'session'];
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo str_pad($ext, 14) . ($loaded ? "OK" : "✗ MISSING — enable this in your hosting control panel (PHP Extensions / Select PHP Version)") . "\n";
}

echo "\nAvailable PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    echo "  ✗ 'sqlite' is not in the list above — this is very likely your 500 error. Ask your host to enable the pdo_sqlite extension.\n";
}

echo "\n--- Folder permissions ---\n";
$dataDir = __DIR__ . '/data';
$uploadsDir = __DIR__ . '/admin/uploads';

foreach (['data' => $dataDir, 'admin/uploads' => $uploadsDir] as $label => $dir) {
    if (!is_dir($dir)) {
        echo "$label: ✗ FOLDER DOES NOT EXIST at $dir\n";
        continue;
    }
    $writable = is_writable($dir);
    echo "$label: " . ($writable ? "writable (OK)" : "✗ NOT WRITABLE — run: chmod 755 " . $label . "  (or 775 if your host requires group-write)") . "\n";
}

echo "\n--- Attempting a real database connection (this is what post-form.php does) ---\n";
try {
    $dbPath = $dataDir . '/blog.sqlite';
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE IF NOT EXISTS _diagnostic_test (id INTEGER PRIMARY KEY)');
    $pdo->exec('DROP TABLE _diagnostic_test');
    echo "Database connection + write test: OK\n";
} catch (Throwable $e) {
    echo "✗ Database connection FAILED:\n";
    echo "  " . $e->getMessage() . "\n";
}

echo "\n--- .htaccess check ---\n";
$dataHt = $dataDir . '/.htaccess';
$uploadsHt = $uploadsDir . '/.htaccess';
echo "data/.htaccess: " . (file_exists($dataHt) ? "present\n" : "missing (not fatal, just less secure)\n");
echo "admin/uploads/.htaccess: " . (file_exists($uploadsHt) ? "present\n" : "missing (not fatal, just less secure)\n");
echo "\nIf a 500 error only happens on pages inside admin/uploads/ or data/, the .htaccess in that\nfolder is likely rejected by your server — see admin/uploads/.htaccess and data/.htaccess in\nthe fix package for a safer version.\n";

echo "\n=== Done. Delete this file (diagnose.php) now. ===\n";
