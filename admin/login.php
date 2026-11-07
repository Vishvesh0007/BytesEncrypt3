<?php
require_once __DIR__ . '/includes/security.php';
be_start_session();

$pdo = be_db();
$error = '';
$locked = be_rate_limited();

if ($locked) {
    $wait = be_seconds_until_unlock();
    $mins = max(1, ceil($wait / 60));
    $error = "Too many failed attempts. Try again in about {$mins} minute" . ($mins === 1 ? '' : 's') . ".";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!be_csrf_verify()) {
        $error = 'Invalid request. Please reload the page and try again.';
    } elseif (!be_captcha_verify($_POST['captcha'] ?? '')) {
        be_record_failed_attempt();
        $error = 'Incorrect answer to the security check. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE username = :u');
        $stmt->execute(['u' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            be_clear_failed_attempts();
            session_regenerate_id(true);
            $_SESSION['admin_id']    = $admin['id'];
            $_SESSION['admin_user']  = $username;
            $_SESSION['last_active'] = time();
            header('Location: dashboard.php');
            exit;
        }

        be_record_failed_attempt();
        $error = 'Incorrect username or password.';
    }
}

[$capA, $capB] = be_captcha_generate();
$token = be_csrf_token();
$timeout = isset($_GET['timeout']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Bytes Encrypt</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-auth-page">
<div class="admin-auth-card">
  <h1>Admin Login</h1>
  <?php if ($timeout): ?><p class="admin-hint">You were signed out after being idle. Please log in again.</p><?php endif; ?>
  <?php if ($error): ?><p class="admin-error"><?= be_e($error) ?></p><?php endif; ?>

  <?php if (!$locked): ?>
  <form method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= be_e($token) ?>">
    <label>Username
      <input type="text" name="username" required autocomplete="username" autofocus>
    </label>
    <label>Password
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    <label>Security check — what is <?= (int) $capA ?> + <?= (int) $capB ?>?
      <input type="text" name="captcha" required inputmode="numeric" autocomplete="off">
    </label>
    <button type="submit" class="admin-btn-primary">Log in</button>
  </form>
  <?php endif; ?>

  <p class="admin-footnote"><a href="../index.html">← Back to site</a></p>
</div>
</body>
</html>
