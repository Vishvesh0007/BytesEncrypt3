<?php
require_once __DIR__ . '/includes/auth.php';
be_require_login();

$pdo = be_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_id'])) {
    if (be_csrf_verify()) {
        $stmt = $pdo->prepare("UPDATE leads SET status = 'contacted' WHERE id = :id");
        $stmt->execute(['id' => (int) $_POST['mark_id']]);
    }
    header('Location: leads.php');
    exit;
}

$leads = $pdo->query('SELECT * FROM leads ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$csrf = be_csrf_field();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consultation Requests — Admin</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-page">
<header class="admin-header">
  <div class="admin-header-inner">
    <span class="admin-logo">BYTES ENCRYPT <span class="admin-logo-tag">Admin</span></span>
    <nav class="admin-nav">
      <a href="dashboard.php">← Dashboard</a>
      <a href="logout.php">Log out</a>
    </nav>
  </div>
</header>

<main class="admin-main">
  <div class="admin-toolbar">
    <h1>Consultation requests</h1>
  </div>

  <?php if (!$leads): ?>
    <p class="admin-empty">No requests yet — they'll show up here as soon as someone submits the contact form.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead>
      <tr><th>Name</th><th>Contact</th><th>Interested in</th><th>Message</th><th>Status</th><th>Received</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($leads as $l): ?>
      <tr>
        <td><?= be_e($l['name']) ?><?= $l['company'] ? '<br><span class="admin-muted">' . be_e($l['company']) . '</span>' : '' ?></td>
        <td>
          <a href="mailto:<?= be_e($l['email']) ?>"><?= be_e($l['email']) ?></a>
          <?php if ($l['phone']): ?><br><span class="admin-muted"><?= be_e($l['phone']) ?></span><?php endif; ?>
        </td>
        <td><?= be_e($l['service_interest'] ?: '—') ?></td>
        <td style="max-width:280px; white-space:pre-wrap;"><?= be_e($l['message']) ?></td>
        <td><span class="admin-status admin-status-<?= $l['status'] === 'new' ? 'draft' : 'published' ?>"><?= be_e(ucfirst($l['status'])) ?></span></td>
        <td><?= be_e($l['created_at']) ?></td>
        <td>
          <?php if ($l['status'] === 'new'): ?>
          <form method="post" style="display:inline;">
            <?= $csrf ?>
            <input type="hidden" name="mark_id" value="<?= (int) $l['id'] ?>">
            <button type="submit" class="admin-row-actions" style="background:none;border:none;color:var(--accent-2);cursor:pointer;font-size:12.5px;padding:0;">Mark contacted</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>
</body>
</html>
