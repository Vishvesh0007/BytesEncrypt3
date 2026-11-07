<?php
/**
 * One-time setup: creates the admins table (via db.php) and lets you
 * create the first admin account. DELETE THIS FILE after you've used it —
 * leaving it live would let anyone create an admin account.
 */
require_once __DIR__ . '/includes/security.php';
be_start_session();

$pdo = be_db();
$hasAdmin = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;

$error = '';
$done  = false;

if ($hasAdmin) {
    $error = 'An admin account already exists. Delete install.php — it is no longer needed.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!be_csrf_verify()) {
        $error = 'Invalid request. Please reload the page and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        if (strlen($username) < 3) {
            $error = 'Username must be at least 3 characters.';
        } elseif (strlen($password) < 12) {
            $error = 'Password must be at least 12 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:u, :p)');
            $stmt->execute([
                'u' => $username,
                'p' => password_hash($password, PASSWORD_BCRYPT),
            ]);
            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Setup — Bytes Encrypt</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-auth-page">
<div class="admin-auth-card">
  <h1>Admin Setup</h1>
  <?php if ($done): ?>
    <p class="admin-success">Admin account created. <strong>Delete install.php now</strong>, then <a href="login.php">log in</a>.</p>
  <?php else: ?>
    <?php if ($error): ?><p class="admin-error"><?= be_e($error) ?></p><?php endif; ?>
    <?php if (!$hasAdmin): ?>
    <form method="post" novalidate>
      <?= be_csrf_field() ?>
      <label>Username
        <input type="text" name="username" required minlength="3" autocomplete="username">
      </label>
      <label>Password
        <input type="password" name="password" required minlength="12" autocomplete="new-password">
      </label>
      <label>Confirm password
        <input type="password" name="confirm" required minlength="12" autocomplete="new-password">
      </label>
      <p class="admin-hint">Use at least 12 characters — this account can publish to your public blog.</p>
      <button type="submit" class="admin-btn-primary">Create admin account</button>
    </form>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
