<?php
require_once __DIR__ . '/includes/auth.php';
be_require_login();

$pdo = be_db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$post = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        header('Location: dashboard.php');
        exit;
    }
}

$errors = [];
$values = $post ?: [
    'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '',
    'featured_image' => '', 'youtube_url' => '', 'category' => '',
    'meta_title' => '', 'meta_description' => '', 'status' => 'draft',
];

function be_slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'post-' . bin2hex(random_bytes(3));
}

/** Extract a YouTube video ID from common URL formats. Returns null if not a YouTube URL. */
function be_youtube_id(string $url): ?string
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    $host = parse_url($url, PHP_URL_HOST) ?? '';
    if (!preg_match('/(^|\.)youtube\.com$|(^|\.)youtu\.be$/i', $host)) {
        return null;
    }
    if (preg_match('/[?&]v=([A-Za-z0-9_-]{6,})/', $url, $m)) {
        return $m[1];
    }
    if (preg_match('#youtu\.be/([A-Za-z0-9_-]{6,})#', $url, $m)) {
        return $m[1];
    }
    if (preg_match('#youtube\.com/embed/([A-Za-z0-9_-]{6,})#', $url, $m)) {
        return $m[1];
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!be_csrf_verify()) {
        $errors[] = 'Invalid request (session expired). Please try again.';
    } else {
        $values['title']             = trim($_POST['title'] ?? '');
        $values['excerpt']           = trim($_POST['excerpt'] ?? '');
        $values['content']           = trim($_POST['content'] ?? '');
        $values['youtube_url']       = trim($_POST['youtube_url'] ?? '');
        $values['category']          = trim($_POST['category'] ?? '');
        $values['meta_title']        = trim($_POST['meta_title'] ?? '');
        $values['meta_description']  = trim($_POST['meta_description'] ?? '');
        $values['status']            = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft';

        $slugInput = trim($_POST['slug'] ?? '');
        $values['slug'] = $slugInput !== '' ? be_slugify($slugInput) : be_slugify($values['title']);

        if ($values['title'] === '') {
            $errors[] = 'Title is required.';
        }
        if ($values['content'] === '') {
            $errors[] = 'Content is required.';
        }
        if ($values['youtube_url'] !== '' && be_youtube_id($values['youtube_url']) === null) {
            $errors[] = 'YouTube link doesn\'t look valid — use a youtube.com or youtu.be URL.';
        }

        // Slug uniqueness (excluding current post)
        if (!$errors) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE slug = :slug AND id != :id');
            $check->execute(['slug' => $values['slug'], 'id' => $id]);
            if ((int) $check->fetchColumn() > 0) {
                $values['slug'] .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            }
        }

        // Image upload (optional)
        if (!$errors && !empty($_FILES['featured_image']['name'])) {
            $file = $_FILES['featured_image'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Image upload failed. Please try again.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Image must be 5MB or smaller.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($file['tmp_name']);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                if (!isset($allowed[$mime])) {
                    $errors[] = 'Image must be JPG, PNG, GIF, or WEBP.';
                } else {
                    $ext = $allowed[$mime];
                    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                    $destDir = __DIR__ . '/uploads';
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    $dest = $destDir . '/' . $filename;
                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $errors[] = 'Could not save the uploaded image.';
                    } else {
                        $values['featured_image'] = 'uploads/' . $filename;
                    }
                }
            }
        }

        if (!$errors) {
            $now = date('Y-m-d H:i:s');
            if ($id) {
                $stmt = $pdo->prepare('
                    UPDATE posts SET title=:title, slug=:slug, excerpt=:excerpt, content=:content,
                        featured_image=:featured_image, youtube_url=:youtube_url, category=:category,
                        meta_title=:meta_title, meta_description=:meta_description, status=:status,
                        updated_at=:updated_at
                    WHERE id=:id
                ');
                $values['updated_at'] = $now;
                $values['id'] = $id;
                $stmt->execute($values);
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO posts (title, slug, excerpt, content, featured_image, youtube_url, category, meta_title, meta_description, status, created_at, updated_at)
                    VALUES (:title, :slug, :excerpt, :content, :featured_image, :youtube_url, :category, :meta_title, :meta_description, :status, :created_at, :updated_at)
                ');
                $stmt->execute(array_merge($values, ['created_at' => $now, 'updated_at' => $now]));
            }
            header('Location: dashboard.php');
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
<title><?= $id ? 'Edit Post' : 'New Post' ?> — Admin</title>
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
  <h1><?= $id ? 'Edit post' : 'New post' ?></h1>

  <?php if ($errors): ?>
    <div class="admin-error-box">
      <ul><?php foreach ($errors as $e): ?><li><?= be_e($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="admin-form" novalidate>
    <?= $csrf ?>
    <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="admin-form-grid">
      <div>
        <label>Title
          <input type="text" name="title" required value="<?= be_e($values['title']) ?>">
        </label>

        <label>URL slug <span class="admin-muted">(leave blank to auto-generate)</span>
          <input type="text" name="slug" value="<?= be_e($values['slug']) ?>" placeholder="auto-generated-from-title">
        </label>

        <label>Category
          <input type="text" name="category" value="<?= be_e($values['category']) ?>" placeholder="e.g. Offensive Security">
        </label>

        <label>Excerpt <span class="admin-muted">(short summary shown on the blog list)</span>
          <textarea name="excerpt" rows="2"><?= be_e($values['excerpt']) ?></textarea>
        </label>

        <label>Content <span class="admin-muted">(basic HTML allowed: &lt;p&gt;, &lt;b&gt;, &lt;a&gt;, &lt;h2&gt;, &lt;ul&gt;, etc.)</span>
          <textarea name="content" rows="14" required><?= be_e($values['content']) ?></textarea>
        </label>
      </div>

      <div>
        <label>Featured image <span class="admin-muted">(JPG/PNG/GIF/WEBP, max 5MB)</span>
          <input type="file" name="featured_image" accept="image/jpeg,image/png,image/gif,image/webp">
        </label>
        <?php if (!empty($values['featured_image'])): ?>
          <img src="<?= be_e($values['featured_image']) ?>" alt="" class="admin-thumb-preview">
        <?php endif; ?>

        <label>YouTube video link <span class="admin-muted">(optional)</span>
          <input type="url" name="youtube_url" value="<?= be_e($values['youtube_url']) ?>" placeholder="https://www.youtube.com/watch?v=...">
        </label>

        <label>Status
          <select name="status">
            <option value="draft" <?= $values['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= $values['status'] === 'published' ? 'selected' : '' ?>>Published</option>
          </select>
        </label>

        <fieldset class="admin-fieldset">
          <legend>SEO</legend>
          <label>Meta title <span class="admin-muted">(falls back to post title)</span>
            <input type="text" name="meta_title" value="<?= be_e($values['meta_title']) ?>" maxlength="70">
          </label>
          <label>Meta description
            <textarea name="meta_description" rows="2" maxlength="160"><?= be_e($values['meta_description']) ?></textarea>
          </label>
        </fieldset>
      </div>
    </div>

    <button type="submit" class="admin-btn-primary">Save post</button>
  </form>
</main>
</body>
</html>
