<?php
require_once __DIR__ . '/includes/auth.php';
be_require_login();

$pdo = be_db();
$jobs = $pdo->query('SELECT id, title, department, location, employment_type, status, created_at FROM jobs ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Job Postings — Admin</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-page">
<header class="admin-header">
  <div class="admin-header-inner">
    <span class="admin-logo">BYTES ENCRYPT <span class="admin-logo-tag">Admin</span></span>
    <nav class="admin-nav">
      <a href="dashboard.php">← Dashboard</a>
      <a href="../careers.php" target="_blank">View careers page ↗</a>
      <a href="logout.php">Log out</a>
    </nav>
  </div>
</header>

<main class="admin-main">
  <div class="admin-toolbar">
    <h1>Job postings</h1>
    <a href="job-form.php" class="admin-btn-primary">+ New job</a>
  </div>

  <?php if (!$jobs): ?>
    <p class="admin-empty">No job postings yet. Create your first one.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead>
      <tr><th>Title</th><th>Department</th><th>Location</th><th>Type</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($jobs as $j): ?>
      <tr>
        <td><?= be_e($j['title']) ?></td>
        <td><?= be_e($j['department'] ?: '—') ?></td>
        <td><?= be_e($j['location'] ?: '—') ?></td>
        <td><?= be_e($j['employment_type'] ?: '—') ?></td>
        <td><span class="admin-status admin-status-<?= be_e($j['status']) ?>"><?= be_e(ucfirst($j['status'])) ?></span></td>
        <td class="admin-row-actions">
          <a href="job-form.php?id=<?= (int) $j['id'] ?>">Edit</a>
          <a href="job-delete.php?id=<?= (int) $j['id'] ?>" class="admin-danger-link" onclick="return confirm('Delete this job posting? This cannot be undone.');">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>
</body>
</html>
