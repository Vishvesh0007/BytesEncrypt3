<?php
require_once __DIR__ . '/includes/auth.php';
be_require_login();

$pdo = be_db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$job = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$job) {
        header('Location: jobs.php');
        exit;
    }
}

$errors = [];
$values = $job ?: [
    'title' => '', 'slug' => '', 'department' => '', 'location' => '',
    'employment_type' => 'Full-time', 'summary' => '', 'description' => '',
    'how_to_apply' => '', 'status' => 'draft',
];

function be_job_slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'job-' . bin2hex(random_bytes(3));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!be_csrf_verify()) {
        $errors[] = 'Invalid request (session expired). Please try again.';
    } else {
        $values['title']           = trim($_POST['title'] ?? '');
        $values['department']      = trim($_POST['department'] ?? '');
        $values['location']        = trim($_POST['location'] ?? '');
        $values['employment_type'] = trim($_POST['employment_type'] ?? '');
        $values['summary']         = trim($_POST['summary'] ?? '');
        $values['description']     = trim($_POST['description'] ?? '');
        $values['how_to_apply']    = trim($_POST['how_to_apply'] ?? '');
        $values['status']          = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft';

        $slugInput = trim($_POST['slug'] ?? '');
        $values['slug'] = $slugInput !== '' ? be_job_slugify($slugInput) : be_job_slugify($values['title']);

        if ($values['title'] === '') {
            $errors[] = 'Title is required.';
        }
        if ($values['description'] === '') {
            $errors[] = 'Description is required.';
        }

        if (!$errors) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE slug = :slug AND id != :id');
            $check->execute(['slug' => $values['slug'], 'id' => $id]);
            if ((int) $check->fetchColumn() > 0) {
                $values['slug'] .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            }
        }

        if (!$errors) {
            $now = date('Y-m-d H:i:s');
            if ($id) {
                $stmt = $pdo->prepare('
                    UPDATE jobs SET title=:title, slug=:slug, department=:department, location=:location,
                        employment_type=:employment_type, summary=:summary, description=:description,
                        how_to_apply=:how_to_apply, status=:status, updated_at=:updated_at
                    WHERE id=:id
                ');
                $values['updated_at'] = $now;
                $values['id'] = $id;
                $stmt->execute($values);
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO jobs (title, slug, department, location, employment_type, summary, description, how_to_apply, status, created_at, updated_at)
                    VALUES (:title, :slug, :department, :location, :employment_type, :summary, :description, :how_to_apply, :status, :created_at, :updated_at)
                ');
                $stmt->execute(array_merge($values, ['created_at' => $now, 'updated_at' => $now]));
            }
            header('Location: jobs.php');
            exit;
        }
    }
}

$csrf = be_csrf_field();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $id ? 'Edit Job' : 'New Job' ?> — Admin</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-page">
<header class="admin-header">
  <div class="admin-header-inner">
    <span class="admin-logo">BYTES ENCRYPT <span class="admin-logo-tag">Admin</span></span>
    <nav class="admin-nav">
      <a href="jobs.php">← Job postings</a>
      <a href="logout.php">Log out</a>
    </nav>
  </div>
</header>

<main class="admin-main">
  <h1><?= $id ? 'Edit job posting' : 'New job posting' ?></h1>

  <?php if ($errors): ?>
    <div class="admin-error-box">
      <ul><?php foreach ($errors as $e): ?><li><?= be_e($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" class="admin-form" novalidate>
    <?= $csrf ?>
    <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="admin-form-grid">
      <div>
        <label>Job title
          <input type="text" name="title" required value="<?= be_e($values['title']) ?>">
        </label>

        <label>URL slug <span class="admin-muted">(leave blank to auto-generate)</span>
          <input type="text" name="slug" value="<?= be_e($values['slug']) ?>" placeholder="auto-generated-from-title">
        </label>

        <label>Short summary <span class="admin-muted">(shown on the careers list)</span>
          <textarea name="summary" rows="2"><?= be_e($values['summary']) ?></textarea>
        </label>

        <label>Full description <span class="admin-muted">(basic HTML allowed)</span>
          <textarea name="description" rows="12" required><?= be_e($values['description']) ?></textarea>
        </label>

        <label>How to apply <span class="admin-muted">(optional — falls back to the standard contact email)</span>
          <textarea name="how_to_apply" rows="3"><?= be_e($values['how_to_apply']) ?></textarea>
        </label>
      </div>

      <div>
        <label>Department
          <input type="text" name="department" value="<?= be_e($values['department']) ?>" placeholder="e.g. Offensive Security">
        </label>

        <label>Location
          <input type="text" name="location" value="<?= be_e($values['location']) ?>" placeholder="e.g. Bangalore, India / Remote">
        </label>

        <label>Employment type
          <select name="employment_type">
            <?php foreach (['Full-time', 'Part-time', 'Contract', 'Internship'] as $t): ?>
              <option <?= $values['employment_type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>Status
          <select name="status">
            <option value="draft" <?= $values['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= $values['status'] === 'published' ? 'selected' : '' ?>>Published</option>
          </select>
        </label>
      </div>
    </div>

    <button type="submit" class="admin-btn-primary">Save job posting</button>
  </form>
</main>
</body>
</html>
