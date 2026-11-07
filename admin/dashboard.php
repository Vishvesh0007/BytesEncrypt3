<?php
require_once __DIR__ . '/includes/auth.php';
be_require_login();

$pdo = be_db();
$posts = $pdo->query('SELECT id, title, slug, status, category, created_at FROM posts ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Admin</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-page">
<header class="admin-header">
  <div class="admin-header-inner">
    <span class="admin-logo">BYTES ENCRYPT <span class="admin-logo-tag">Admin</span></span>
    <nav class="admin-nav">
      <span>Signed in as <?= be_e($_SESSION['admin_user'] ?? '') ?></span>
      <a href="leads.php">Consultation requests</a>
      <a href="jobs.php">Job postings</a>
      <a href="../blog.php" target="_blank">View blog ↗</a>
      <a href="logout.php">Log out</a>
    </nav>
  </div>
</header>

<main class="admin-main">
  <div class="admin-toolbar">
    <h1>Blog posts</h1>
    <a href="post-form.php" class="admin-btn-primary">+ New post</a>
  </div>

  <?php if (!$posts): ?>
    <p class="admin-empty">No posts yet. Create your first one.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead>
      <tr><th>Title</th><th>Category</th><th>Status</th><th>Created</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($posts as $p): ?>
      <tr>
        <td><?= be_e($p['title']) ?><br><span class="admin-muted">/<?= be_e($p['slug']) ?></span></td>
        <td><?= be_e($p['category'] ?: '—') ?></td>
        <td><span class="admin-status admin-status-<?= be_e($p['status']) ?>"><?= be_e(ucfirst($p['status'])) ?></span></td>
        <td><?= be_e($p['created_at']) ?></td>
        <td class="admin-row-actions">
          <a href="post-form.php?id=<?= (int) $p['id'] ?>">Edit</a>
          <a href="../blog-post.php?slug=<?= urlencode($p['slug']) ?>" target="_blank">View</a>
          <a href="post-delete.php?id=<?= (int) $p['id'] ?>" class="admin-danger-link" onclick="return confirm('Delete this post? This cannot be undone.');">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>
</body>
</html>
